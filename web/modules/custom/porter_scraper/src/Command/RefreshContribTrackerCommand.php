<?php

namespace Drupal\porter_scraper\Command;

use Drupal\Console\Command\ContainerAwareCommand;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshContribTrackerCommand extends ContainerAwareCommand {
  /** @var OutputInterface|null */
  protected $stdOut;
  /** @var OutputInterface|null */
  protected $stdErr;
  /** @var  InputInterface|null */
  protected $stdIn;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var \Drupal\porter_scraper\ProjectGroupingManagerInterface
   */
  protected $projectGroupingManager;

  protected $trackerStatusMap = [
    1 => 'No port started',
    13 => 'Port in development',
    8 => 'Alpha or Beta D8 releases available',
    14 => 'Release Candidate (RC) release',
    2 => 'Stable release',
    7 => 'Stable release',
    4 => 'Port is blocked',
    16 => 'Needs research',
    5 => 'Deprecated/In Core',
    3 => 'Renamed/Obsolete/No Port',
  ];

  /**
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $taxonomyMap = [];

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('porter:refresh:tracker')
      ->setDescription('Imports new projects into the system based on project grouping plugins.');
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $this->stdOut = $output;
    $this->stdErr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    $this->stdIn = $input;

    $this->client = new Client([
      'base_uri' => 'https://www.drupal.org/api-d7',
      'headers' => [
        'Accept' => 'application/json',
      ]
    ]);
    $this->projectGroupingManager = $this->getContainer()->get('plugin.manager.project_grouping');

    // Find taxonomy terms referencing our mapping.
    $term_storage = $this->entityTypeManager()->getStorage('taxonomy_term');

    foreach ($this->trackerStatusMap as $remote_tid => $label) {
      $query = $term_storage->getQuery();
      $query->condition('vid', 'contrib_tracker_status');
      $query->condition('field_remote_status_id', $remote_tid);
      $query->range(0, 1);
      $results = $query->execute();

      if (empty($results)) {
        $term = $term_storage->create([
          'vid' => 'contrib_tracker_status',
          'name' => $label,
          'field_remote_status_id‎' => $remote_tid,
        ]);
        $term->save();
        $this->taxonomyMap[$remote_tid] = $term;
      }
      else {
        $this->taxonomyMap[$remote_tid] = $term_storage->load(reset($results));
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $data = $this->getData('https://www.drupal.org/api-d7/node.json?type=project_issue&field_project=2573607&page=0');
    $this->processList($data->list);

    while (isset($data->next)) {
      $next_url = str_replace('/node', '/node.json', $data->next);
      $this->stdOut->writeln("Processing: " . $next_url);

      $data = $this->getData($next_url);
      $this->processList($data->list);
    }
  }

  /**
   * Processes a URL and returns the result.
   *
   * @param string $url
   *    The URL to fetch.
   * @return object
   *    The decoded JSON response.
   */
  protected function getData($url) {
    $response = $this->client->get($url);
    $data = json_decode($response->getBody()->getContents());
    return $data;
  }

  protected function processList($list) {
    /** @var object $item */
    foreach ($list as $item) {
      /** @var \Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface[] $matches */
      $matches = $this->projectGroupingManager->findMatch($item->title);

      if (empty($matches)) {
        if ($this->stdOut->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
          $this->stdOut->writeln("<info>No matches found for {$item->title}</info>");
        }
      }
      else {
        /** @var \Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface $match */
        $match = reset($matches);
        $regex_matches = [];
        preg_match("/{$match->getRegex('contrib_tracker')}/", $item->title, $regex_matches);
        $machine_name = str_replace(['[', ']'], ['', ''], $regex_matches[1]);

        if ($this->stdOut->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
          $this->stdOut->writeln("<info>Matches found for {$machine_name}</info>");
        }

        /** @var \Drupal\node\NodeStorageInterface $node_storage */
        $node_storage = $this->entityTypeManager()->getStorage('node');
        $query = $node_storage->getQuery();
        $query->condition('field_project_machine_name', $machine_name);
        $results = $query->execute();

        if (!empty($results)) {
          $this->stdOut->writeln("<info>Updating {$item->title}</info>");

          $node = $node_storage->load(reset($results));
          $node->field_contrib_tracker_status = [
            'target_id' => $this->taxonomyMap[$item->field_issue_status]->id()
          ];
          $node->field_contrib_tracker = [
            'uri' => $item->url,
          ];
          $node->save();
        }
      }
    }
  }
}

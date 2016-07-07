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
      $matches = $this->projectGroupingManager->findMatch($item->field_project_machine_name);

      if (empty($matches)) {
        $this->stdOut->writeln("<info>No matches found for {$item->title}</info>");
      } else {
        /** @var \Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface $match */
        $match = reset($matches);
        $regex_matches = [];
        preg_match("/{$match->getRegex('contrib_tracker')}/", $item->field_project_machine_name, $regex_matches);
        $machine_name = str_replace(['[', ']'], ['', ''], $regex_matches[1]);

        /** @var \Drupal\node\NodeStorageInterface $node_storage */
        $node_storage = $this->entityTypeManager()->getStorage('node');
        $query = $node_storage->getQuery();
        $query->condition('field_project_machine_name', $machine_name);
        $results = $query->execute();

        if (!empty($results)) {
          $node = $node_storage->load(reset($results));
          $node->field_contrib_tracker_status = $item->field_issue_status;
          $node->field_contrib_tracker = $item->url;
        }
      }
    }
  }
}

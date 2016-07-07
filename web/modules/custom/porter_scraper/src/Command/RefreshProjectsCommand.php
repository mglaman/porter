<?php

namespace Drupal\porter_scraper\Command;

use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshProjectsCommand extends ContainerAwareCommand {

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
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $taxonomyMap = [];

  /**
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('porter:refresh:projects')
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
    $this->nodeStorage = $this->entityTypeManager()->getStorage('node');

    // Find taxonomy terms referencing our plugins.
    $term_storage = $this->entityTypeManager()->getStorage('taxonomy_term');
    $query = $term_storage->getQuery();
    $query->condition('vid', 'grouping');
    $results = $query->execute();
    /** @var \Drupal\taxonomy\TermInterface $terms */
    $terms = $term_storage->loadMultiple($results);
    foreach ($terms as $term) {
      $plugin_reference = $term->field_project_grouping_plugin->value;
      if (!empty($plugin_reference)) {
        $this->taxonomyMap[$plugin_reference][] = $term;
      }
    }

  }


  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $data = $this->getData('https://www.drupal.org/api-d7/node.json?type=project_module&page=0');
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
      $matches = $this->projectGroupingManager->findMatch($item->field_project_machine_name);

      if ($this->stdOut->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
        if (empty($matches)) {
          $this->stdOut->writeln("<info>No matches found for {$item->field_project_machine_name}</info>");
        } else {
          $this->stdOut->writeln("<info>Matches found for {$item->field_project_machine_name}</info>");
        }
      }

      foreach ($matches as $match) {
        $this->handleValidProject($item, $match);
      }
    }
  }

  protected function handleValidProject($item, ProjectGroupingInterface $grouping) {
    $query = $this->nodeStorage->getQuery();
    $query->condition('field_project_machine_name', $item->field_project_machine_name);
    $results = $query->execute();

    if (empty($results)) {
      $values = [
        'type' => 'project',
        'title' => $item->title,
        'field_project_machine_name' => $item->field_project_machine_name,
        'field_project_page' => $item->url,
        'field_grouping' => [],
      ];
      foreach ($this->taxonomyMap[$grouping->getId()] as $taxonomy_term) {
        $values['field_grouping'][] = $taxonomy_term;
      }
      $node = $this->nodeStorage->create($values);
      $node->save();
      $this->stdOut->writeln("Saved entry for project: " . $node->label());
    }
  }
}

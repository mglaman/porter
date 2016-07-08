<?php

namespace Drupal\porter_scraper\Scraper;

use Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface;

class ModuleProjectsScraper extends RemoteScraperBase {

  /**
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $taxonomyMap = [];

  /**
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  protected function preRun() {
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');

    // Find taxonomy terms referencing our plugins.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
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


  protected function getRemoteUrl() {
    return 'https://www.drupal.org/api-d7/node.json?type=project_module&page=0';
  }

  protected function processList($list) {
    /** @var object $item */
    foreach ($list as $item) {
      $matches = $this->projectGroupingManager->findMatch($item->field_project_machine_name);

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
    }
  }
}

<?php

namespace Drupal\porter_scraper\Scraper;

class ContribTrackerScraper extends RemoteScraperBase {

  protected $trackerStatusMap = [
    1 => 'No port started',
    13 => 'Port in development',
    8 => 'Alpha or Beta',
    14 => 'Release Candidate (RC)',
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

  public function getId() {
    return 'contrib_tracker';
  }

  protected function preRun() {
    // Find taxonomy terms referencing our mapping.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

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


  protected function getRemoteUrl() {
    return 'https://www.drupal.org/api-d7/node.json?type=project_issue&field_project=2573607&page=' . $this->currentPage;
  }

  protected function processList($list) {
    /** @var object $item */
    foreach ($list as $item) {
      /** @var \Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface[] $matches */
      $matches = $this->projectGroupingManager->findMatch($item->title);

      if (!empty($matches)) {
        /** @var \Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface $match */
        $match = reset($matches);
        $regex_matches = [];
        preg_match("/{$match->getRegex('contrib_tracker')}/", $item->title, $regex_matches);
        $machine_name = str_replace(['[', ']'], ['', ''], $regex_matches[1]);

        /** @var \Drupal\node\NodeStorageInterface $node_storage */
        $node_storage = $this->entityTypeManager->getStorage('node');
        $query = $node_storage->getQuery();
        $query->condition('field_project_machine_name', $machine_name);
        $results = $query->execute();

        if (!empty($results)) {
          $node = $node_storage->load(reset($results));

          // Fixed and Closed (Fixed) are same thing.
          $issue_status = $item->field_issue_status;
          if ($issue_status == 2) {
            $issue_status = 7;
          }

          $node->field_contrib_tracker_status = [
            'target_id' => $this->taxonomyMap[$issue_status]->id()
          ];
          $node->field_contrib_tracker = [
            'uri' => $item->url,
          ];
          $node->save();
          \Drupal::logger('contrib_tracker_craper')->info("Updated {$node->label()} contrib tracker status.");
        }
      }
    }
  }

}

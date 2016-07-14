<?php

namespace Drupal\porter_scraper\Scraper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\porter_scraper\ProjectGroupingManagerInterface;

abstract class RemoteScraperBase {
  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\porter_scraper\ProjectGroupingManagerInterface
   */
  protected $projectGroupingManager;

  protected $pageLimit;

  protected $currentPage = 0;

  /**
   * RemoteScraperBase constructor.
   *
   * @todo make page limit work. set to 1000 right now. need to save last page somewhere and pick up there next run.
   *
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\porter_scraper\ProjectGroupingManagerInterface $project_grouping_manager
   * @param int $page_limit
   */
  public function __construct(ClientFactory $client_factory, EntityTypeManagerInterface $entity_type_manager, ProjectGroupingManagerInterface $project_grouping_manager, $page_limit = 1000) {
    $this->client = $client_factory->fromOptions([
      'headers' => [
        'Accept' => 'application/json',
      ]
    ]);
    $this->entityTypeManager = $entity_type_manager;
    $this->projectGroupingManager = $project_grouping_manager;
    $this->pageLimit = $page_limit;
  }

  public function run() {
    $this->preRun();

    $data = $this->getData($this->getRemoteUrl());
    $this->processList($data->list);
    $this->currentPage++;

    // While our current page count is less than total, keep going.
    while (isset($data->next) && $this->currentPage < $this->pageLimit) {
      $next_url = str_replace('/node', '/node.json', $data->next);
      $data = $this->getData($next_url);
      sleep(rand(10,45));
      $this->processList($data->list);
    }

    $this->postRun();
  }

  public function getPageLimit() {
    return $this->pageLimit;
  }

  public function setPageLimit($int) {
    $this->pageLimit = $int;
    return $this;
  }

  protected function preRun() {}
  protected function postRun() {}

  abstract protected function getRemoteUrl();

  abstract protected function processList($list);

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
}

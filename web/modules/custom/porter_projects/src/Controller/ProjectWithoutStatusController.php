<?php

namespace Drupal\porter_projects\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProjectWithoutStatusController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;

  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function output(TermInterface $taxonomy_term) {
    if ($taxonomy_term->bundle() != 'grouping') {
      return [];
    }

    $build = [
      '#theme' => 'item_list',
      '#items' => [],
    ];

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $results = $node_storage
      ->getQuery()
      ->condition('field_grouping', $taxonomy_term->id())
      ->notExists('field_contrib_tracker_status')
      ->execute();

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $node_storage->loadMultiple($results);
    foreach ($nodes as $node) {
      $build['#items'][] = $node->label();
    }

    return $build;
  }

}

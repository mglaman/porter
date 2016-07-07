<?php

namespace Drupal\porter_scraper;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides the default project_grouping manager.
 */
class ProjectGroupingManager extends DefaultPluginManager implements ProjectGroupingManagerInterface {

  /**
   * Provides default values for all project_grouping plugins.
   *
   * @var array
   */
  protected $defaults = array(
    // Add required and optional plugin properties.
    'id' => '',
    'label' => '',
    'regex' => [
      'machine_name' => NULL,
      'contrib_tracker' => NULL,
    ],
    'class' => 'Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingDefault'
  );

  /**
   * Constructs a ProjectGroupingManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    // Add more services as required.
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'project_grouping', array('project_grouping'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('project_grouping', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // You can add validation of the plugin definition here.
    if (empty($definition['id'])) {
      throw new PluginException(sprintf('Example plugin property (%s) definition "is" is required.', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findMatch($string) {
    $matches = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      /** @var \Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface $instance */
      $instance = $this->createInstance($id, $definition);
      if ($instance->hasMatch($string)) {
        $matches[$id] = $instance;
      }
    }

    return $matches;
  }


}

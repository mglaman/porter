<?php

namespace Drupal\porter_scraper\Plugin\ProjectGrouping;

use Drupal\Core\Plugin\PluginBase;

class ProjectGroupingDefault extends PluginBase implements ProjectGroupingInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }
  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRegex($type) {
    $regexes = $this->pluginDefinition['regex'];

    return isset($regexes[$type]) ? $regexes[$type] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMatch($string) {
    foreach ($this->pluginDefinition['regex'] as $item) {
      if (preg_match("/$item/", $string) === 1) {
        return TRUE;
      }
    }

    return FALSE;
  }

}

<?php

namespace Drupal\porter_scraper;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for project_grouping managers.
 */
interface ProjectGroupingManagerInterface extends PluginManagerInterface {

  /**
   * Attempts to find a match with the available plugins using regex.
   *
   * @param string $string
   *    The string to check
   * @return \Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface[]
   */
  public function findMatch($string);

}

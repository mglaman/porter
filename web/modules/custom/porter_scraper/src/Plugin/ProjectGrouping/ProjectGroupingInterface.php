<?php

namespace Drupal\porter_scraper\Plugin\ProjectGrouping;

interface ProjectGroupingInterface {

  /**
   * Gets the workflow ID.
   *
   * @return string
   *   The workflow ID.
   */
  public function getId();
  /**
   * Gets the translated label.
   *
   * @return string
   *   The translated label.
   */
  public function getLabel();


  /**
   * Returns a regex format for the plugin.
   *
   * @param $type
   * @return mixed
   */
  public function getRegex($type);

  /**
   * Checks if the string matches against any of the plugin's regex.
   *
   * @param $string
   * @return mixed
   */
  public function hasMatch($string);
}

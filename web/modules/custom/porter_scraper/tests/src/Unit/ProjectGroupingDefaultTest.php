<?php

namespace Drupal\Tests\porter_scraper\Unit;

use Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingDefault;
use Drupal\Tests\UnitTestCase;

class ProjectGroupingDefaultTest extends UnitTestCase {

  public function testPlugin() {
    $plugin = new ProjectGroupingDefault([], 'test_grouping', [
      'id' => 'test',
      'label' => 'Testing',
      'regex' => [
        'number' => '^[0-9]*$',
        'string' => '^[a-zA-Z]*$',
      ],
    ]);

    $this->assertEquals('test', $plugin->getId());
    $this->assertEquals('Testing', $plugin->getLabel());
    $this->assertEquals('^[0-9]*$', $plugin->getRegex('number'));
    $this->assertEquals('^[a-zA-Z]*$', $plugin->getRegex('string'));

    $this->assertFalse($plugin->hasMatch('21243jddd2'));
    $this->assertTrue($plugin->hasMatch('1234564'));
    $this->assertTrue($plugin->hasMatch('mymodule'));
  }

}

<?php

namespace Drupal\porter_scraper\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\porter_scraper\Scraper\ContribTrackerScraper;

/**
 * Class ContribTrackerScraperCommand.
 *
 * @DrupalCommand (
 *     extension="porter_scraper",
 *     extensionType="module"
 * )
 */
class ContribTrackerScraperCommand extends Command {

  /**
   * Drupal\porter_scraper\Scraper\ContribTrackerScraper definition.
   *
   * @var \Drupal\porter_scraper\Scraper\ContribTrackerScraper
   */
  protected $porterScraperScraperContribTracker;

  /**
   * Constructs a new ContribTrackerScraperCommand object.
   */
  public function __construct(ContribTrackerScraper $porter_scraper_scraper_contrib_tracker) {
    $this->porterScraperScraperContribTracker = $porter_scraper_scraper_contrib_tracker;
    parent::__construct();
  }
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('porter:scraper:contrib-tracker')
      ->setDescription($this->trans('commands.porter.scraper.contrib-tracker.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->porterScraperScraperContribTracker->setPageLimit(200)->run();
    $io = new DrupalStyle($input, $output);
    $io->info($this->trans('commands.porter.scraper.contrib-tracker.messages.success'));
  }
}

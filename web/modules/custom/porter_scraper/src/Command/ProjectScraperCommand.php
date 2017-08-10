<?php

namespace Drupal\porter_scraper\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\porter_scraper\Scraper\ModuleProjectsScraper;

/**
 * Class ProjectScraperCommand.
 *
 * @DrupalCommand (
 *     extension="porter_scraper",
 *     extensionType="module"
 * )
 */
class ProjectScraperCommand extends Command {

  /**
   * Drupal\porter_scraper\Scraper\ModuleProjectsScraper definition.
   *
   * @var \Drupal\porter_scraper\Scraper\ModuleProjectsScraper
   */
  protected $porterScraperScraperModuleProjects;

  /**
   * Constructs a new ProjectScraperCommand object.
   */
  public function __construct(ModuleProjectsScraper $porter_scraper_scraper_module_projects) {
    $this->porterScraperScraperModuleProjects = $porter_scraper_scraper_module_projects;
    parent::__construct();
  }
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('porter:scrape:projects')
      ->setDescription($this->trans('commands.porter.scrape.projects.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->porterScraperScraperModuleProjects->setPageLimit(200)->run();
    $io = new DrupalStyle($input, $output);
    $io->info($this->trans('commands.porter.scrape.projects.messages.success'));
  }
}

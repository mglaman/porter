<?php

namespace Drupal\porter_scraper\Command;

use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\porter_scraper\Plugin\ProjectGrouping\ProjectGroupingInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshProjectsCommand extends ContainerAwareCommand {

  /** @var OutputInterface|null */
  protected $stdOut;
  /** @var OutputInterface|null */
  protected $stdErr;
  /** @var  InputInterface|null */
  protected $stdIn;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var \Drupal\porter_scraper\ProjectGroupingManagerInterface
   */
  protected $projectGroupingManager;

  /**
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $taxonomyMap = [];

  /**
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('porter:refresh:projects')
      ->setDescription('Imports new projects into the system based on project grouping plugins.');
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $this->stdOut = $output;
    $this->stdErr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    $this->stdIn = $input;

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getContainer()
      ->get('porter_scraper.scraper.module_projects')
      ->setPageLimit(200)
      ->run();
  }

}

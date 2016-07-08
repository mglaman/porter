<?php

namespace Drupal\porter_scraper\Command;

use Drupal\Console\Command\ContainerAwareCommand;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshContribTrackerCommand extends ContainerAwareCommand {
  /** @var OutputInterface|null */
  protected $stdOut;
  /** @var OutputInterface|null */
  protected $stdErr;
  /** @var  InputInterface|null */
  protected $stdIn;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('porter:refresh:tracker')
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
      ->get('porter_scraper.scraper.contrib_tracker')
      ->setPageLimit(200)
      ->run();
  }
}

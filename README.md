# Porter [![CircleCI](https://circleci.com/gh/mglaman/porter.svg?style=svg)](https://circleci.com/gh/mglaman/porter)

Porter is a Drupal 8 site that scrapes the Drupal.org project list and the contrib tracker project.

The goal is to provide something similar to ContribKanban.com, but for porting contributed projects in Drupal's various microcosms. This can be used to: see porting status, help reduce duplicate efforts, find ways to deprecate contributed
project requirements.

## Install

1. Clone
1. `composer install`
1. Install Drupal
1. `drush config-set system.site uuid 1e6703e9-9173-4ba9-b0d8-3e2e1f001cf0 --yes`
1. `drupal config:import`
1. Add a "Grouping" term for each `commerce` and `workbench` grouping plugins (plugin reference field.)
1. `drupal porter:refresh:projects`
1. `drupal porter:refresh:tracker`

Beware, the `porter:refresh:projects` command can be taxing on the Drupal.org API; so don't run it often.
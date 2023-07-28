<?php

define('DXP_PROJECT_ENVIRONMENT', 'local');
define('PURGE_READY_FOR_USE', TRUE);

/**
 * @file
 * The additional settings for the local environment.
 */

include_once DRUPAL_ROOT . '/../etc/drupal/general.settings.php';

/**
 * Configure splits
 */
$config['config_split.config_split.local']['status'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Trusted host settings.
 */
$settings['trusted_host_patterns'] = [
  '^(.+)$',
];

/**
 * Error logging.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Lando settings, copied from lando.settings.php
 */
$lando_info = getenv('LANDO_INFO');
if (!$lando_info) {
  return;
}

$lando = json_decode($lando_info, TRUE);

/**
 * Database credentials.
 */
$databases['default']['default'] = [
  'database' => $lando['database']['creds']['database'],
  'username' => $lando['database']['creds']['user'],
  'password' => $lando['database']['creds']['password'],
  'prefix' => '',
  'host' => $lando['database']['internal_connection']['host'],
  'port' => $lando['database']['internal_connection']['port'],
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/lando.services.yml';

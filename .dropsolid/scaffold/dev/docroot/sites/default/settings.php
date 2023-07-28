<?php

define('DXP_PROJECT_ENVIRONMENT', '[[ environment_name ]]');
define('PURGE_READY_FOR_USE', TRUE);

$databases = [
  'default' =>
    [
      'default' =>
        [
          'database' => '[[ database_name ]]',
          'username' => '[[ database_user ]]',
          'password' => '[[ database_password ]]',
          'host' => '[[ database_host ]]',
          'port' => '[[ database_port ]]',
          'driver' => 'mysql',
          'prefix' => '',
        ],
    ],
];

$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = array_merge(
  ['127.0.0.1'],
  explode(',', '[[ proxy_ips ]]')
);

/**
 * Private files folder path
 */
$settings['file_temp_path'] = "../tmp";

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 */
$settings['hash_salt'] = '[[ env_secret_key ]]';

/**
 * Trusted host settings.
 */
$settings['trusted_host_patterns'] = [
  '^mauticorg\.[[ environment_name ]]\.sites\.dropsolid-sites\.com$'
];

/**
 * Configure splits
 */
$config['config_split.config_split.[[ environment_name ]]']['status'] = TRUE;

// Include general settings
if (file_exists(DRUPAL_ROOT . '/../etc/drupal/general.settings.php')) {
  include_once DRUPAL_ROOT . '/../etc/drupal/general.settings.php';
}

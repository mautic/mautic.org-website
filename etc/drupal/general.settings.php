<?php

// Ignore front-end folders.
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

// Setup config directory.
$settings['config_sync_directory'] = DRUPAL_ROOT . '/../config/sync';

// Setup files folders.
$settings['file_private_path'] = '../private/files';
$settings['file_public_path'] = 'sites/default/files';

/**
 * Memcache.
 */
$memcache_exists = class_exists('Memcache', FALSE);
$memcached_exists = class_exists('Memcached', FALSE);
$memcache_module_is_present = file_exists(
  DRUPAL_ROOT . '/modules/contrib/memcache/memcache.services.yml'
);

if ($memcache_module_is_present && ($memcache_exists || $memcached_exists)) {
  // Use Memcached extension if available.
  if ($memcached_exists) {
    $settings['memcache']['extension'] = 'Memcached';
  }

  if (class_exists('Composer\Autoload\ClassLoader')) {
    $class_loader = new Composer\Autoload\ClassLoader();
    $class_loader->addPsr4(
      'Drupal\\memcache\\',
      'modules/contrib/memcache/src'
    );
    $class_loader->register();

    $settings['container_yamls'][] = DRUPAL_ROOT . '/modules/contrib/memcache/memcache.services.yml';
    $settings['bootstrap_container_definition'] = [
      'parameters' => [],
      'services' => [
        # Dependencies.
        'settings' => [
          'class' => 'Drupal\Core\Site\Settings',
          'factory' => 'Drupal\Core\Site\Settings::getInstance',
        ],
        'memcache.settings' => [
          'class' => 'Drupal\memcache\MemcacheSettings',
          'arguments' => ['@settings'],
        ],
        'memcache.factory' => [
          'class' => 'Drupal\memcache\Driver\MemcacheDriverFactory',
          'arguments' => ['@memcache.settings'],
        ],
        'memcache.timestamp.invalidator.bin' => [
          'class' => 'Drupal\memcache\Invalidator\MemcacheTimestampInvalidator',
          'arguments' => [
            '@memcache.factory',
            'memcache_bin_timestamps',
            0.001,
          ],
        ],
        'memcache.timestamp.invalidator.tag' => [
          'class' => 'Drupal\memcache\Invalidator\MemcacheTimestampInvalidator',
          'arguments' => [
            '@memcache.factory',
            'memcache_tag_timestamps',
            0.001,
          ],
        ],
        'memcache.backend.cache.container' => [
          'class' => 'Drupal\memcache\DrupalMemcacheInterface',
          'factory' => ['@memcache.factory', 'get'],
          'arguments' => ['container'],
        ],
        'cache_tags_provider.container' => [
          'class' => 'Drupal\memcache\Cache\TimestampCacheTagsChecksum',
          'arguments' => ['@memcache.timestamp.invalidator.tag'],
        ],
        'cache.container' => [
          'class' => 'Drupal\memcache\MemcacheBackend',
          'arguments' => [
            'container',
            '@memcache.backend.cache.container',
            '@cache_tags_provider.container',
            '@memcache.timestamp.invalidator.bin',
          ],
        ],
      ],
    ];

    // Override default fastchained backend for static bins.
    // @see https://www.drupal.org/node/2754947
    $settings['cache']['bins']['bootstrap'] = 'cache.backend.memcache';
    $settings['cache']['bins']['discovery'] = 'cache.backend.memcache';
    $settings['cache']['bins']['config'] = 'cache.backend.memcache';
    // Use memcache as the default bin.
    $settings['cache']['default'] = 'cache.backend.memcache';
    // Use database backend for forms
    $settings['cache']['bins']['form'] = 'cache.backend.database';
    // Enable stampede protection.
    $settings['memcache']['stampede_protection'] = TRUE;
    // Enable memcache locking.
    $settings['container_yamls'][] = __DIR__ . '/../memcache.services.yml';
    // Set prefix
    $settings['memcache']['key_prefix'] = 'mauticorg_' . DXP_PROJECT_ENVIRONMENT;
  }
}

// Varnish Purge Rocketship setup.
if (PURGE_READY_FOR_USE && DXP_PROJECT_ENVIRONMENT) {
  $config['dropsolid_purge.config'] = [
    'site_name' => 'mauticorg',
    'site_environment' => DXP_PROJECT_ENVIRONMENT,
    'site_group' => "MauticOrg",
    'loadbalancers' => [
      'varnish' => [
        'ip' => '127.0.0.1',
        'protocol' => 'http',
        'port' => '88',
      ],
    ],
  ];
}

/**
 * Settings files.
 */
$extra_settings_files = [
  // This file was created for each environment, you can find it by SSH-ing to a given environment
  DRUPAL_ROOT . "/../secrets/secrets.settings.php",
];

foreach ($extra_settings_files as $extra_settings_file) {
  if (file_exists($extra_settings_file)) {
    require $extra_settings_file;
  }
}

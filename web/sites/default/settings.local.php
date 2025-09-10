<?php

// Local development overrides to disable caches and enable verbose debugging.

// Ensure this file is not committed to production.
// In Git, add sites/*/settings.local.php to .gitignore.

// Disable all render caches by using the null backend for various bins.
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
$settings['cache']['bins']['discovery'] = 'cache.backend.null';
$settings['cache']['bins']['config'] = 'cache.backend.null';

// Optionally disable bootstrap and data caches too (more aggressive).
$settings['cache']['bins']['bootstrap'] = 'cache.backend.null';
$settings['cache']['bins']['data'] = 'cache.backend.null';

// Skip permissions hardening to speed up local dev.
$settings['skip_permissions_hardening'] = TRUE;

// Disable CSS/JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Enable verbose error logging and display.
$config['system.logging']['error_level'] = 'verbose';

// Use the local development services to disable Twig cache, etc.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Enable development.services.yml overrides for twig debug and no cache.
$config['twig.config']['debug'] = TRUE;
$config['twig.config']['cache'] = FALSE;
$config['twig.config']['auto_reload'] = TRUE;

$settings['config_sync_directory'] = '../config/sync';

$settings['trusted_host_patterns'] = [
    '^mass-specc\.ddev\.site$',
];
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = ['127.0.0.1'];

if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
  $_SERVER['HTTPS'] = 'on';
}

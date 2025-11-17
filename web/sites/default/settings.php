<?php

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(DRUPAL_ROOT . '/../.env');


$databases = [];
$settings['hash_salt'] = 'gkBtVxMl8U9V74tbwK_Pmfwmt2Zwk_UXGtyyGpMSv9f8GjenT5z3TK1ph-pXsbCld4u2kNS7lw';
$settings['update_free_access'] = FALSE;

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;
$settings['state_cache'] = TRUE;

$settings['migrate_node_migrate_type_classic'] = FALSE;

if (getenv('IS_DDEV_PROJECT') == 'true' && file_exists(__DIR__ . '/settings.ddev.php')) {
  include __DIR__ . '/settings.ddev.php';
}

$settings['file_private_path'] = '/var/www/html/private';//envar

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

// if (file_exists($app_root . '/' . $site_path . '/settings.docker.php')) {
//   include $app_root . '/' . $site_path . '/settings.docker.php';
// }
if (getenv('APP_PROFILE') === 'prod') {
  $databases['default']['default'] = [
    'database' => getenv('DB_NAME') ?: 'drupal',
    'username' => getenv('DB_USERNAME') ?: 'drupal',
    'password' => getenv('DB_PASSWORD') ?: 'drupal',
    'host' => getenv('DB_HOST') ?: 'postgresql',
    'port' => getenv('DB_PORT') ?: '5432',
    'driver' => 'pgsql',
  ];

  if (file_exists(DRUPAL_ROOT . '/sites/default/files') || TRUE) {
    $env = function ($name, $fallback = NULL) {
      $val = getenv($name);
      return ($val === false) ? $fallback : $val;
    };

    $smtp_on = filter_var($env('DRUPAL_SMTP_ON', 'true'), FILTER_VALIDATE_BOOLEAN);
    $smtp_host = $env('DRUPAL_SMTP_HOST', 'placeholder');
    $smtp_port = $env('DRUPAL_SMTP_PORT', '587');
    $smtp_username = $env('DRUPAL_SMTP_USERNAME', '');
    $smtp_password = $env('DRUPAL_SMTP_PASSWORD', '');
    $smtp_protocol = $env('DRUPAL_SMTP_PROTOCOL', 'tls');
    $smtp_autotls = filter_var($env('DRUPAL_SMTP_AUTOTLS', 'true'), FILTER_VALIDATE_BOOLEAN);
    $smtp_from = $env('DRUPAL_SMTP_FROM', 'noreply@mass-specc.com');
    $smtp_fromname = $env('DRUPAL_SMTP_FROMNAME', 'noreply@mass-specc');
    $smtp_timeout = (int) $env('DRUPAL_SMTP_TIMEOUT', 30);
    $smtp_allowhtml = filter_var($env('DRUPAL_SMTP_ALLOWHTML', 'false'), FILTER_VALIDATE_BOOLEAN);

    $config['smtp.settings']['smtp_on'] = $smtp_on;
    $config['smtp.settings']['smtp_host'] = $smtp_host;
    $config['smtp.settings']['smtp_hostbackup'] = '';
    $config['smtp.settings']['smtp_port'] = (string) $smtp_port;
    $config['smtp.settings']['smtp_protocol'] = $smtp_protocol;
    $config['smtp.settings']['smtp_autotls'] = $smtp_autotls;
    $config['smtp.settings']['smtp_timeout'] = $smtp_timeout;
    $config['smtp.settings']['smtp_username'] = $smtp_username;
    $config['smtp.settings']['smtp_password'] = $smtp_password;
    $config['smtp.settings']['smtp_from'] = $smtp_from;
    $config['smtp.settings']['smtp_fromname'] = $smtp_fromname;
    $config['smtp.settings']['smtp_allowhtml'] = $smtp_allowhtml;
    $config['smtp.settings']['smtp_debugging'] = filter_var($env('DRUPAL_SMTP_DEBUGGING', 'false'), FILTER_VALIDATE_BOOLEAN);
    $config['smtp.settings']['smtp_debug_level'] = (int) $env('DRUPAL_SMTP_DEBUG_LEVEL', 4);
    $config['smtp.settings']['smtp_keepalive'] = filter_var($env('DRUPAL_SMTP_KEEPALIVE', 'false'), FILTER_VALIDATE_BOOLEAN);
  }

}



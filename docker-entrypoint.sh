#!/usr/bin/env bash

# set -e

cd /opt/drupal

# Site init
BOOTSTRAP_STATUS="$(composer exec -q drush status --field=bootstrap --format=list 2>/dev/null || true)"

if [[ "$BOOTSTRAP_STATUS" != "Successful" ]]; then
  echo "Drupal not installed; running site:install..."
  composer exec drush site:install
  composer exec drush cset system.site uuid "${SITE_UUID}"
  composer exec drush ev "\Drupal::entityTypeManager()->getStorage('shortcut')->delete(\Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple());"
  composer exec drush ev "\Drupal::entityTypeManager()->getStorage('shortcut_set')->delete(\Drupal::entityTypeManager()->getStorage('shortcut_set')->loadMultiple());"
else
  echo "Drupal already installed; skipping site:install."
fi

# Core drupal modules
composer exec drush pm:enable field text node comment block_content taxonomy contact shortcut

# MASS-SPECC modules
composer exec drush pm:enable common login password_reset user_dropdown admin cooperative

# Config import
composer exec drush cim
composer exec drush cim
composer exec drush cr
apache2-foreground
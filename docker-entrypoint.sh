#!/usr/bin/env bash

# set -e

cd /opt/drupal

# Site init
BOOTSTRAP_STATUS="$(composer exec -q drush status --field=bootstrap --format=list 2>/dev/null || true)"

if [[ "$BOOTSTRAP_STATUS" != "Successful" ]]; then
  echo "Drupal not installed; running site:install..."

  drush site:install --account-name=${ADMIN_USERNAME:-drupal} --account-pass=${ADMIN_PASSWORD:-drupal}
  drush user:role:add administrator "${ADMIN_USERNAME:-drupal}"
  drush cset system.site uuid "${SITE_UUID}"
  drush ev "\Drupal::entityTypeManager()->getStorage('shortcut')->delete(\Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple());"
  drush ev "\Drupal::entityTypeManager()->getStorage('shortcut')->delete(\Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple());"
else
  echo "Drupal already installed; skipping site:install."
fi

# Core drupal modules
drush pm:enable field text node comment block_content taxonomy contact shortcut

# MASS-SPECC modules
drush pm:enable common login password_reset user_dropdown admin cooperative

# Config import
drush cim
drush cim
drush cr
gpg --import CIC_TestEnv_PubKey.asc
apache2-foreground

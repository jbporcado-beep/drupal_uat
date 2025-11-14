#!/usr/bin/env bash

# set -e

cd /opt/drupal

# Site init
BOOTSTRAP_STATUS="$(composer exec -q drush status --field=bootstrap --format=list 2>/dev/null || true)"

if [[ "$BOOTSTRAP_STATUS" != "Successful" ]]; then
  echo "Drupal not installed; running site:install..."

  drush site:install --account-name=${ADMIN_USERNAME:-drupal} --account-pass=${ADMIN_PASSWORD:-drupal}
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
drush updb
drush im --choice=full
drush cr
gpg --import CIC_TestEnv_PubKey.asc
drush user:role:add administrator "${ADMIN_USERNAME:-drupal}"

drush ev "\$e = \\Drupal::configFactory()->getEditable('smtp.settings'); \$e->set('smtp_password','')->save();" || true
drush cr || true

drush cget smtp.settings --format=yaml > /opt/drupal/config/sync/smtp.settings.yml || true
chown www-data:www-data /opt/drupal/config/sync/smtp.settings.yml || true
chmod 644 /opt/drupal/config/sync/smtp.settings.yml || true

if [ -f /run/secrets/drupal_smtp_password ]; then
  RAW_PW="$(cat /run/secrets/drupal_smtp_password)"
else
  RAW_PW="${DRUPAL_SMTP_PASSWORD:-}"
fi
CLEAN_PW="$(printf '%s' "$RAW_PW" | tr -d '\r' | sed -e 's/[[:space:]]\+$//')"
unset RAW_PW

if [ -n "$CLEAN_PW" ]; then
  export CLEAN_PW
  drush ev "\$p = trim(getenv('CLEAN_PW') ?: ''); if (\$p) { \\Drupal::configFactory()->getEditable('smtp.settings')->set('smtp_password', \$p)->save(); }" || true
  unset CLEAN_PW
  drush cr || true
fi
apache2-foreground

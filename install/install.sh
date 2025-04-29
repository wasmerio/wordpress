#! /usr/bin/bash

set -e

# Needed to get the WP-CLI commands to avoid asking for the TTY size, which
# doesn't work because we don't have the stty command it uses.
export COLUMNS=80

echo "Creating required directories..."

mkdir -p /app/wp-content/plugins
echo "" > /app/wp-content/plugins/.keep

mkdir -p /app/wp-content/upgrade
echo "" > /app/wp-content/upgrade/.keep

echo "Installing WordPress core..."

php /app/wp-cli.phar \
  --debug \
  --allow-root \
  --path=/app \
  core install \
  --url="$WASMER_APP_URL"  \
  --title="$WP_SITE_TITLE" \
  --admin_user="$WP_ADMIN_USERNAME" \
  --admin_password="$WP_ADMIN_PASSWORD" \
  --admin_email="$WP_ADMIN_EMAIL" \
  --locale="$WP_LOCALE" || true


echo "Installing theme..."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  wasmer-aio-install install \
  --locale="$WP_LOCALE" \
  --theme=/app/install/twentytwentyfive.zip || true

echo "Installation complete"

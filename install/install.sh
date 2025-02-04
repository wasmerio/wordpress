#! /usr/bin/bash

set -e

echo "Installing WP . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  core install \
  --url="$WASMER_APP_URL"  \
  --title="$WP_SITE_TITLE" \
  --admin_user="$WP_ADMIN_USERNAME" \
  --admin_password="$WP_ADMIN_PASSWORD" \
  --admin_email="$WP_ADMIN_EMAIL" \
  --locale="$WP_LOCALE"

echo "Installing default theme . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  theme install /app/install/twentytwentyfive.zip

echo "Installing theme language . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  language theme install --all "$WP_LOCALE"

mkdir -p /app/wp-content/plugins
touch /app/wp-content/plugins/.keep
touch /app/wp-content/upgrade/.keep

echo "Installation complete!"
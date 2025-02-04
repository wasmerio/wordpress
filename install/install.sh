#! /usr/bin/bash

set -e

echo "Creating WP plugins directory . . ."

mkdir -p /app/wp-content/plugins
touch /app/wp-content/plugins/.keep

mkdir -p /app/wp-content/upgrade
touch /app/wp-content/upgrade/.keep

echo "Installing WP . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  core install \
  --url="$WASMER_APP_URL"  \
  --title="$WP_SITE_TITLE" \
  --admin_user="$WP_ADMIN_USERNAME" \
  --admin_password="$WP_ADMIN_PASSWORD" \
  --admin_email="$WP_ADMIN_EMAIL"

echo "Installing default theme . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  theme install /app/install/twentytwentyfive.zip

echo "Installing language . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  language core install --activate "$WP_LOCALE"

echo "Installing theme language . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  language theme install --all "$WP_LOCALE"

echo "Installation complete!"

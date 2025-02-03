#! /usr/bin/bash

set -eo

echo "Installing WP . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  core install \
  --url=$WP_INSTALL_APP_DOMAIN \
  --title=$WP_INSTALL_TITLE \
  --admin_user=$WP_INSTALL_USER \
  --admin_password=$WP_INSTALL_PASSWORD \
  --admin_email=$WP_INSTALL_EMAIL \
  --locale=$WP_INSTALL_LANGUAGE

echo "Installing default theme . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  theme install /app/install/twentytwentyfive.zip

echo "Installing theme language . . ."

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  language theme install --all $WP_INSTALL_LANGUAGE
#! /usr/bin/bash

set -eo

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  --debug \
  core install \
  --url=$WP_INSTALL_APP_DOMAIN \
  --title=$WP_INSTALL_TITLE \
  --admin_user=$WP_INSTALL_USER \
  --admin_password=$WP_INSTALL_PASSWORD \
  --admin_email=$WP_INSTALL_EMAIL \
  --locale=$WP_INSTALL_LANGUAGE

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  --debug \
  theme install /app/install/twentytwentyfive.zip

php /app/wp-cli.phar \
  --allow-root \
  --path=/app \
  --debug \
  language theme install --all $WP_INSTALL_LANGUAGE
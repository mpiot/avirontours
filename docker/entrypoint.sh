#!/bin/bash
set -e

# Decrypt secrets
if [[ -z "${SYMFONY_DECRYPTION_SECRET}" ]]; then
  bin/console secrets:decrypt-to-local --force --env=prod
fi

# Apply migrations to database
php -r "set_time_limit(60);for(;;){if(@fsockopen('database',5432)){break;}echo \"Waiting for PostgreSQL\n\";sleep(1);}"
bin/console doctrine:migration:migrate -n

# Clear the cache and warmup it
bin/console cache:clear --no-warmup
bin/console cache:warmup
chown -R www-data var

exec "$@"

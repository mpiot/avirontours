#!/bin/bash
set -e

bin/console secrets:decrypt-to-local --force --env=prod
php -r "set_time_limit(60);for(;;){if(@fsockopen('database',5432)){break;}echo \"Waiting for Postgres\n\";sleep(1);}"
bin/console doctrine:migration:migrate -n
bin/console cache:clear --no-warmup
bin/console cache:warmup
chown -R www-data var

exec "$@"

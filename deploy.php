<?php

declare(strict_types=1);

/*
 * Copyright 2020 Mathieu Piot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Deployer;

require 'contrib/npm.php';
require 'recipe/common.php';

// Config

set('repository', 'git@github.com:mpiot/avirontours.git');
set('keep_releases', 10);

set('shared_files', [
    'config/secrets/prod/prod.decrypt.private.php',
]);
set('shared_dirs', [
    'var/log',
    'var/sessions',
    'protected_files',
]);
set('writable_dirs', [
    'var',
    'var/cache',
    'var/log',
    'var/sessions',
]);

set('bin/console', '{{bin/php}} {{release_or_current_path}}/bin/console');

// Hosts

host('141.94.65.219')
    ->set('deploy_path', '/mnt/app')
    ->set('branch', 'develop')
    ->set('symfony_env', 'prod')
;

// Hooks

after('deploy:symlink', 'database:migrate');
after('deploy:symlink', 'php:restart');
after('deploy:symlink', 'app:stop-workers');
after('deploy:failed', 'deploy:unlock');

// Tasks

desc('Migrates database');
task('database:migrate', function () {
    run('cd {{release_or_current_path}} && {{bin/console}} doctrine:migrations:migrate --allow-no-migration --all-or-nothing --no-interaction');
});

desc('Installs vendors');
task('deploy:vendors', function () {
    if (!commandExist('unzip')) {
        warning('To speed up composer installation setup "unzip" command with PHP zip extension.');
    }
    run('cd {{release_or_current_path}} && APP_ENV={{symfony_env}} {{bin/composer}} {{composer_action}} {{composer_options}} 2>&1');
});

desc('Build assets');
task('assets:build', function () {
    run('cd {{release_path}} && npm run build');
});

desc('Stop workers');
task('app:stop-workers', function (): void {
    run('cd {{release_or_current_path}} && {{bin/console}} messenger:stop-workers');
});

desc('Restart PHP');
task('php:restart', function (): void {
    run('sudo systemctl restart php8.3-fpm');
});

desc('Optimize symfony environment files');
task('symfony:optimize_env_files', function (): void {
    run('cd {{release_or_current_path}} && {{bin/console}} secrets:decrypt-to-local --force --env={{symfony_env}}');
    run('cd {{release_or_current_path}} && composer dump-env {{symfony_env}}');
    run('cd {{release_or_current_path}} && rm .env .env.prod .env.test .env.{{symfony_env}}.local');
});

desc('Deploys project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'npm:install',
    'assets:build',
    'symfony:optimize_env_files',
    'deploy:publish',
]);

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

require 'recipe/common.php';

// Project name
set('application', 'avirontours');

// Project repository
set('repository', 'git@github.com:mpiot/avirontours.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
set('shared_files', ['config/secrets/prod/prod.decrypt.private.php']);
set('shared_dirs', ['var/log', 'var/sessions', 'protected_files']);

// Writable dirs by web server
set('writable_dirs', ['var']);
set('allow_anonymous_stats', false);

// Console access
set('bin/console', fn () => parse('{{release_path}}/bin/console'));

// Hosts
host('rhea.avirontours.fr')
    ->set('deploy_path', '/var/www/{{application}}')
    ->roles('app')
    ->stage('prod')
    ->set('branch', 'develop')
;

// Tasks
desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:assets',
    'deploy:optimize',
    'deploy:clear_paths',
    'deploy:migrate',
    'deploy:symlink',
    'php:restart',
    'workers:restart',
    'deploy:unlock',
    'cleanup',
    'success',
]);

// Custom deploy
task('deploy:vendors', function (): void {
    run('cd {{release_path}} && APP_ENV={{stage}} {{bin/composer}} {{composer_options}}');
});

task('deploy:assets', function (): void {
    if (has('previous_release')) {
        if (test('[ -d {{previous_release}}/node_modules ]')) {
            run('cp -R {{previous_release}}/node_modules {{release_path}}');
        }
    }

    run('cd {{release_path}} && yarn install');
    run('cd {{release_path}} && yarn build');
});

task('deploy:optimize', function (): void {
    run('cd {{release_path}} && {{bin/php}} {{bin/console}} secrets:decrypt-to-local --force --env={{stage}}');
    run('cd {{release_path}} && composer dump-env {{stage}}');
    run('cd {{release_path}} && rm .env .env.prod .env.test .env.{{stage}}.local');
});

task('deploy:migrate', function (): void {
    run('cd {{release_path}} && {{bin/php}} {{bin/console}} doctrine:migration:migrate -n');
});

task('php:restart', function (): void {
    run('sudo systemctl restart php8.1-fpm');
});

task('workers:restart', function (): void {
    run('cd {{release_path}} && {{bin/php}} {{bin/console}} messenger:stop-workers');
});

// If deploy fails, automatically unlock.
after('deploy:failed', 'deploy:unlock');

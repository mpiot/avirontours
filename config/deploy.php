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

use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class() extends DefaultDeployer {
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('tethys.avirontours.fr')
            ->deployDir('/var/www/avirontours')
            ->repositoryUrl('git@github.com:mpiot/avirontours.git')
            ->repositoryBranch('develop')
            ->composerInstallFlags('--no-dev --prefer-dist --no-interaction')
            ->sharedFilesAndDirs(['config/secrets/prod/prod.decrypt.private.php', 'protected_files', 'var/log', 'var/sessions'])
            ->fixPermissionsWithAcl('www-data')
        ;
    }

    public function beforePreparing(): void
    {
        $this->log('<h3>Copying over the .env files</>');
        $this->runRemote('cp {{ deploy_dir }}/repo/.env {{ project_dir }}/.env');
        $this->runRemote('cp {{ deploy_dir }}/repo/.env.prod {{ project_dir }}/.env.prod');
    }

    public function beforeOptimizing(): void
    {
        $this->log('<h3>Build assets</>');
        $this->runRemote('yarn install');
        $this->runRemote('yarn build');
        $this->runRemote('rm -R node_modules');

        $this->log('<h3>Optimizing environments</>');
        $this->runRemote('{{ console_bin }} secrets:decrypt-to-local --force --env=prod');
        $this->runRemote('composer dump-env prod');
        $this->runRemote('rm .env .env.prod .env.prod.local');
    }

    public function beforeFinishingDeploy(): void
    {
        $this->log('<h3>Restart PHP FPM for Preloading</>');
        $this->runRemote('sudo systemctl restart php8.0-fpm');
        $this->log('<h3>Migrate the database</>');
        $this->runRemote('{{ console_bin }} doctrine:migration:migrate');
    }
};

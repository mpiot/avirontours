<?php

use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class extends DefaultDeployer
{
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('app.avirontours.fr:2222')
            ->deployDir('/var/www/app')
            ->repositoryUrl('git@github.com:mpiot/atm-management.git')
            ->repositoryBranch('develop')
            ->composerInstallFlags( '--no-dev --prefer-dist --no-interaction')
            ->sharedFilesAndDirs(['config/secrets/prod/prod.decrypt.private.php'])
        ;
    }

    public function beforePreparing()
    {
        $this->log('<h3>Copying over the .env files</>');
        $this->runRemote('cp {{ deploy_dir }}/repo/.env {{ project_dir }}/.env');
        $this->runRemote('cp {{ deploy_dir }}/repo/.env.prod {{ project_dir }}/.env.prod');

        $this->log('<h3>Build assets</>');
        $this->runRemote('yarn install');
        $this->runRemote('yarn build');
        $this->runRemote('rm -R node_modules');
    }

    public function beforeOptimizing()
    {
        $this->log('<h3>Optimizing environments</>');
        $this->runRemote('{{ console_bin }} secrets:decrypt-to-local --force --env=prod');
        $this->runRemote('composer dump-env prod');
        $this->runRemote('rm .env .env.prod .env.prod.local');
    }

    public function beforeFinishingDeploy()
    {
        $this->log('<h3>Migrate the databae</>');
        $this->runRemote('{{ console_bin }} doctrine:migration:migrate');
    }
};

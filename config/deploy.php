<?php

use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class extends DefaultDeployer
{
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('my.avirontours.fr:2222')
            ->deployDir('/var/www/avirontours')
            ->repositoryUrl('git@github.com:mpiot/avirontours.git')
            ->repositoryBranch('develop')
            ->composerInstallFlags( '--no-dev --prefer-dist --no-interaction')
            ->sharedFilesAndDirs(['config/secrets/prod/prod.decrypt.private.php', 'protected_files', 'var/log', 'var/sessions'])
            ->fixPermissionsWithAcl('www-data')
        ;
    }

    public function beforePreparing()
    {
        $this->log('<h3>Copying over the .env files</>');
        $this->runRemote('cp {{ deploy_dir }}/repo/.env {{ project_dir }}/.env');
        $this->runRemote('cp {{ deploy_dir }}/repo/.env.prod {{ project_dir }}/.env.prod');
    }

    public function beforeOptimizing()
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

    public function beforeFinishingDeploy()
    {
        $this->log('<h3>Restart PHP FPM for Preloading</>');
        $this->runRemote('sudo systemctl restart php7.4-fpm');
        $this->log('<h3>Migrate the database</>');
        $this->runRemote('{{ console_bin }} doctrine:migration:migrate');
    }
};

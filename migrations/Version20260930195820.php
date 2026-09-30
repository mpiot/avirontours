<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930195820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clear the distance of trainings whose sport does not take one';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE training SET distance = NULL WHERE sport NOT IN ('rowing', 'ergometer', 'cycling', 'running', 'swimming', 'other')");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The cleared distances cannot be restored.');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001162135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the resting heart rate to the physiology';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE physiology ADD resting_heart_rate INT DEFAULT NULL');
        $this->addSql('UPDATE physiology SET resting_heart_rate = 60');
        $this->addSql('ALTER TABLE physiology ALTER resting_heart_rate SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE physiology DROP resting_heart_rate');
    }
}

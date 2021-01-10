<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210110141743 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE physiology RENAME COLUMN light_aerobic_heart_rate TO light_aerobic_heart_rate_min');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN heavy_aerobic_heart_rate TO heavy_aerobic_heart_rate_min');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN anaerobic_threshold_heart_rate TO anaerobic_threshold_heart_rate_min');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN oxygen_transportation_heart_rate TO oxygen_transportation_heart_rate_min');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN anaerobic_heart_rate TO anaerobic_heart_rate_min');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE physiology RENAME COLUMN light_aerobic_heart_rate_min TO light_aerobic_heart_rate');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN heavy_aerobic_heart_rate_min TO heavy_aerobic_heart_rate');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN anaerobic_threshold_heart_rate_min TO anaerobic_threshold_heart_rate');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN oxygen_transportation_heart_rate_min TO oxygen_transportation_heart_rate');
        $this->addSql('ALTER TABLE physiology RENAME COLUMN anaerobic_heart_rate_min TO anaerobic_heart_rate');
    }
}

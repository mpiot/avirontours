<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210110144101 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE physiology ALTER light_aerobic_heart_rate_min SET NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER heavy_aerobic_heart_rate_min SET NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER anaerobic_threshold_heart_rate_min SET NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER oxygen_transportation_heart_rate_min SET NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER anaerobic_heart_rate_min SET NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER maximum_heart_rate SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE physiology ALTER light_aerobic_heart_rate_min DROP NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER heavy_aerobic_heart_rate_min DROP NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER anaerobic_threshold_heart_rate_min DROP NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER oxygen_transportation_heart_rate_min DROP NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER anaerobic_heart_rate_min DROP NOT NULL');
        $this->addSql('ALTER TABLE physiology ALTER maximum_heart_rate DROP NOT NULL');
    }
}

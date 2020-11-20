<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201120114011 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD lane_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD lane_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD lane_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP lane_number');
        $this->addSql('ALTER TABLE app_user DROP lane_type');
        $this->addSql('ALTER TABLE app_user DROP lane_name');
    }
}

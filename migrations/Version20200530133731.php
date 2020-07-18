<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200530133731 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE medical_certificate ADD file_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_certificate ADD file_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_certificate ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE medical_certificate DROP file_name');
        $this->addSql('ALTER TABLE medical_certificate DROP file_size');
        $this->addSql('ALTER TABLE medical_certificate DROP updated_at');
    }
}

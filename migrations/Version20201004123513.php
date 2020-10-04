<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201004123513 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE license ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('UPDATE license SET created_at = medical_certificate.updated_at, updated_at = medical_certificate.updated_at FROM medical_certificate WHERE license.medical_certificate_id = medical_certificate.id');
        $this->addSql('UPDATE license SET created_at = \'2020-09-01 10:00:00\', updated_at = \'2020-09-01 10:00:00\' WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE license ALTER COLUMN created_at SET NOT NULL ');
        $this->addSql('ALTER TABLE license ALTER COLUMN updated_at SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license DROP created_at');
        $this->addSql('ALTER TABLE license DROP updated_at');
    }
}

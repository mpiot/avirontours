<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330091015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE measure_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE measure (id INT NOT NULL, user_id INT NOT NULL, measured_at DATE NOT NULL, type VARCHAR(255) NOT NULL, value DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_80071925A76ED395 ON measure (user_id)');
        $this->addSql('COMMENT ON COLUMN measure.measured_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE measure ADD CONSTRAINT FK_80071925A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE measure_id_seq CASCADE');
        $this->addSql('ALTER TABLE measure DROP CONSTRAINT FK_80071925A76ED395');
        $this->addSql('DROP TABLE measure');
    }
}

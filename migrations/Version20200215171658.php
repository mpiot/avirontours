<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200215171658 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE address_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE address (id INT NOT NULL, number VARCHAR(255) NOT NULL, lane_type VARCHAR(255) NOT NULL, lane_name VARCHAR(255) NOT NULL, postal_code INT NOT NULL, city VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, usable VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE member ADD address_id INT NOT NULL');
        $this->addSql('ALTER TABLE member ADD civility VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE member ADD license_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE member ADD email VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE member ADD birthday DATE NOT NULL');
        $this->addSql('ALTER TABLE member ADD legal_representative VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE member ADD subscription_date DATE NOT NULL');
        $this->addSql('ALTER TABLE member DROP licensed_to_row');
        $this->addSql('ALTER TABLE member ALTER license_end_at DROP NOT NULL');
        $this->addSql('ALTER TABLE member ADD CONSTRAINT FK_70E4FA78F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70E4FA78F5B7AF75 ON member (address_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE member DROP CONSTRAINT FK_70E4FA78F5B7AF75');
        $this->addSql('DROP SEQUENCE address_id_seq CASCADE');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP INDEX UNIQ_70E4FA78F5B7AF75');
        $this->addSql('ALTER TABLE member ADD licensed_to_row BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE member DROP address_id');
        $this->addSql('ALTER TABLE member DROP civility');
        $this->addSql('ALTER TABLE member DROP license_type');
        $this->addSql('ALTER TABLE member DROP email');
        $this->addSql('ALTER TABLE member DROP birthday');
        $this->addSql('ALTER TABLE member DROP legal_representative');
        $this->addSql('ALTER TABLE member DROP subscription_date');
        $this->addSql('ALTER TABLE member ALTER license_end_at SET NOT NULL');
    }
}

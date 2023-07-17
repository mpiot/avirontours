<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230717080749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE license_payment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE license_payment (id INT NOT NULL, license_id INT NOT NULL, method VARCHAR(255) NOT NULL, amount INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8431CF56460F904B ON license_payment (license_id)');
        $this->addSql('ALTER TABLE license_payment ADD CONSTRAINT FK_8431CF56460F904B FOREIGN KEY (license_id) REFERENCES license (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE license_payment_id_seq CASCADE');
        $this->addSql('ALTER TABLE license_payment DROP CONSTRAINT FK_8431CF56460F904B');
        $this->addSql('DROP TABLE license_payment');
    }
}

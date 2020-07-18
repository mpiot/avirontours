<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200312202147 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE address ALTER number DROP NOT NULL');
        $this->addSql('ALTER TABLE address ALTER lane_type DROP NOT NULL');
        $this->addSql('ALTER TABLE address ALTER lane_name DROP NOT NULL');
        $this->addSql('ALTER TABLE address ALTER postal_code DROP NOT NULL');
        $this->addSql('ALTER TABLE address ALTER city DROP NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE address ALTER number SET NOT NULL');
        $this->addSql('ALTER TABLE address ALTER lane_type SET NOT NULL');
        $this->addSql('ALTER TABLE address ALTER lane_name SET NOT NULL');
        $this->addSql('ALTER TABLE address ALTER postal_code SET NOT NULL');
        $this->addSql('ALTER TABLE address ALTER city SET NOT NULL');
    }
}

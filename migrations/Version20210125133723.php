<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125133723 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training ALTER duration TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE training ALTER duration DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN training.duration IS \'(DC2Type:dateinterval)\'');
        $this->addSql('ALTER TABLE training_phase ALTER duration TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE training_phase ALTER duration DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN training_phase.duration IS \'(DC2Type:dateinterval)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training ALTER duration TYPE TIME(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE training ALTER duration DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN training.duration IS NULL');
        $this->addSql('ALTER TABLE training_phase ALTER duration TYPE TIME(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE training_phase ALTER duration DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN training_phase.duration IS NULL');
    }
}

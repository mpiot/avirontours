<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329181224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training_phase ALTER times DROP NOT NULL');
        $this->addSql('ALTER TABLE training_phase ALTER distances DROP NOT NULL');
        $this->addSql('ALTER TABLE training_phase ALTER paces DROP NOT NULL');
        $this->addSql('ALTER TABLE training_phase ALTER stroke_rates DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE training_phase ALTER times SET NOT NULL');
        $this->addSql('ALTER TABLE training_phase ALTER distances SET NOT NULL');
        $this->addSql('ALTER TABLE training_phase ALTER paces SET NOT NULL');
        $this->addSql('ALTER TABLE training_phase ALTER stroke_rates SET NOT NULL');
    }
}

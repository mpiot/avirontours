<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260801193357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add training.concept2_id (unique per user) to de-duplicate Concept2 Logbook imports';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training ADD concept2_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D5128A8FA76ED3955544E7C2 ON training (user_id, concept2_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D5128A8FA76ED3955544E7C2');
        $this->addSql('ALTER TABLE training DROP concept2_id');
    }
}

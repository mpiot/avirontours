<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220108100509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ALTER roles SET NOT NULL');
        $this->addSql('ALTER TABLE license ALTER marking SET NOT NULL');
        $this->addSql('ALTER TABLE license ALTER transition_contexts SET NOT NULL');
        $this->addSql('ALTER TABLE logbook_entry ALTER non_user_crew_members SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE logbook_entry ALTER non_user_crew_members DROP NOT NULL');
        $this->addSql('ALTER TABLE license ALTER marking DROP NOT NULL');
        $this->addSql('ALTER TABLE license ALTER transition_contexts DROP NOT NULL');
        $this->addSql('ALTER TABLE app_user ALTER roles DROP NOT NULL');
    }
}

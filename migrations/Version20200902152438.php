<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200902152438 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD club_email_allowed BOOLEAN');
        $this->addSql('ALTER TABLE app_user ADD partners_email_allowed BOOLEAN');
        $this->addSql('ALTER TABLE license ADD federation_email_allowed BOOLEAN ');
        $this->addSql('UPDATE app_user SET club_email_allowed = true, partners_email_allowed = false');
        $this->addSql('UPDATE license SET federation_email_allowed = false');
        $this->addSql('ALTER TABLE app_user ALTER club_email_allowed SET NOT NULL');
        $this->addSql('ALTER TABLE app_user ALTER partners_email_allowed SET NOT NULL');
        $this->addSql('ALTER TABLE license ALTER federation_email_allowed SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license DROP federation_email_allowed');
        $this->addSql('ALTER TABLE app_user DROP club_email_allowed');
        $this->addSql('ALTER TABLE app_user DROP partners_email_allowed');
    }
}

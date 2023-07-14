<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230714072209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shell_damage DROP CONSTRAINT FK_BD5C86F721C3AF14');
        $this->addSql('ALTER TABLE shell_damage ADD CONSTRAINT FK_BD5C86F721C3AF14 FOREIGN KEY (logbook_entry_id) REFERENCES logbook_entry (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE shell_damage DROP CONSTRAINT fk_bd5c86f721c3af14');
        $this->addSql('ALTER TABLE shell_damage ADD CONSTRAINT fk_bd5c86f721c3af14 FOREIGN KEY (logbook_entry_id) REFERENCES logbook_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}

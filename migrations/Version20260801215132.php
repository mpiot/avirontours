<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260801215132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index the hot logbook_entry(date, end_at) and training(trained_at) columns';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_7E7DB59AAA9E377A ON logbook_entry (date)');
        $this->addSql('CREATE INDEX IDX_7E7DB59A37D3107C ON logbook_entry (end_at)');
        $this->addSql('CREATE INDEX IDX_D5128A8F5B147FD9 ON training (trained_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_7E7DB59AAA9E377A');
        $this->addSql('DROP INDEX IDX_7E7DB59A37D3107C');
        $this->addSql('DROP INDEX IDX_D5128A8F5B147FD9');
    }
}

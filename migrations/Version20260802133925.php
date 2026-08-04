<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802133925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill empty license markings with the workflow initial marking';
    }

    public function up(Schema $schema): void
    {
        // Licenses created before the initial marking was applied at creation time were stored
        // with an empty marking ([]). Backfill them with the workflow's initial marking.
        // The column is `json` (no equality operator), so compare through a jsonb cast.
        $this->addSql(<<<'SQL'
            UPDATE license
            SET marking = '{"wait_medical_certificate_validation": 1, "wait_payment_validation": 1}'
            WHERE marking::jsonb = '[]'::jsonb
               OR marking::jsonb = '{}'::jsonb
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE license
            SET marking = '[]'
            WHERE marking::jsonb = '{"wait_medical_certificate_validation": 1, "wait_payment_validation": 1}'::jsonb
            SQL);
    }
}

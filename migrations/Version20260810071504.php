<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810071504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the feeling as a percentage, and let a session carry none.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training ALTER feeling TYPE INT USING ROUND(feeling * 100)');
        $this->addSql('ALTER TABLE training ALTER feeling DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training ALTER feeling TYPE DOUBLE PRECISION USING feeling / 100.0');
    }
}

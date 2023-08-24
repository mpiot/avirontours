<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230717115210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license ADD uuid UUID DEFAULT NULL');
        $this->addSql('UPDATE license SET uuid = gen_random_uuid() WHERE uuid IS NULL');
        $this->addSql('ALTER TABLE license ALTER COLUMN uuid SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN license.uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5768F419D17F50A6 ON license (uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_5768F419D17F50A6');
        $this->addSql('ALTER TABLE license DROP uuid');
    }
}

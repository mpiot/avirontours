<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804122057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE license_payment SET method = \'yeps\' WHERE method = \'yelp\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE license_payment SET method = \'yelp\' WHERE method = \'yeps\'');
    }
}

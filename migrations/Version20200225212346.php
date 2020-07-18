<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200225212346 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE member RENAME COLUMN civility TO gender');
        $this->addSql('UPDATE member SET gender = \'m\' WHERE gender = \'homme\'');
        $this->addSql('UPDATE member SET gender = \'f\' WHERE gender = \'femme\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE member RENAME COLUMN gender TO civility');
        $this->addSql('UPDATE member SET civility = \'homme\' WHERE civility = \'m\'');
        $this->addSql('UPDATE member SET civility = \'femme\' WHERE civility = \'f\'');
    }
}

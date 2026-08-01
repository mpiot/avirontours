<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260801180731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow deleting a user: cascade reset_password_request, set uploaded_file blameable columns to null';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT fk_7ce748aa76ed395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE uploaded_file DROP CONSTRAINT fk_b40df75db03a8386');
        $this->addSql('ALTER TABLE uploaded_file DROP CONSTRAINT fk_b40df75d896dbbde');
        $this->addSql('ALTER TABLE uploaded_file ADD CONSTRAINT FK_B40DF75DB03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE uploaded_file ADD CONSTRAINT FK_B40DF75D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT fk_7ce748aa76ed395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE uploaded_file DROP CONSTRAINT FK_B40DF75DB03A8386');
        $this->addSql('ALTER TABLE uploaded_file DROP CONSTRAINT FK_B40DF75D896DBBDE');
        $this->addSql('ALTER TABLE uploaded_file ADD CONSTRAINT fk_b40df75db03a8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE uploaded_file ADD CONSTRAINT fk_b40df75d896dbbde FOREIGN KEY (updated_by_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}

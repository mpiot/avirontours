<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200212212511 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE invitation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE shell_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE shell_damage_category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE logbook_entry_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE shell_damage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE app_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE invitation (id INT NOT NULL, member_id INT NOT NULL, code VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F11D61A27597D3FE ON invitation (member_id)');
        $this->addSql('CREATE TABLE shell (id INT NOT NULL, name VARCHAR(255) NOT NULL, number_rowers INT NOT NULL, coxed BOOLEAN NOT NULL, rowing_type VARCHAR(255) NOT NULL, yolette BOOLEAN NOT NULL, enabled BOOLEAN NOT NULL, abbreviation VARCHAR(255) NOT NULL, production_year INT DEFAULT NULL, weight_category INT DEFAULT NULL, new_price DOUBLE PRECISION DEFAULT NULL, mileage DOUBLE PRECISION NOT NULL, rigger_material VARCHAR(255) DEFAULT NULL, rigger_position VARCHAR(255) DEFAULT NULL, usage_frequency INT DEFAULT NULL, rower_category INT NOT NULL, personal_boat BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE shell_damage_category (id INT NOT NULL, name VARCHAR(255) NOT NULL, priority INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE member (id INT NOT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, license_number VARCHAR(255) DEFAULT NULL, licensed_to_row BOOLEAN NOT NULL, license_end_at DATE NOT NULL, rower_category INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70E4FA78A76ED395 ON member (user_id)');
        $this->addSql('CREATE TABLE logbook_entry (id INT NOT NULL, shell_id INT NOT NULL, date DATE NOT NULL, start_at TIME(0) WITHOUT TIME ZONE NOT NULL, end_at TIME(0) WITHOUT TIME ZONE DEFAULT NULL, covered_distance DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7E7DB59A13AF1BA4 ON logbook_entry (shell_id)');
        $this->addSql('CREATE TABLE logbook_entry_member (logbook_entry_id INT NOT NULL, member_id INT NOT NULL, PRIMARY KEY(logbook_entry_id, member_id))');
        $this->addSql('CREATE INDEX IDX_2CA459E21C3AF14 ON logbook_entry_member (logbook_entry_id)');
        $this->addSql('CREATE INDEX IDX_2CA459E7597D3FE ON logbook_entry_member (member_id)');
        $this->addSql('CREATE TABLE shell_damage (id INT NOT NULL, category_id INT NOT NULL, shell_id INT NOT NULL, logbook_entry_id INT DEFAULT NULL, description TEXT DEFAULT NULL, done_at DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BD5C86F712469DE2 ON shell_damage (category_id)');
        $this->addSql('CREATE INDEX IDX_BD5C86F713AF1BA4 ON shell_damage (shell_id)');
        $this->addSql('CREATE INDEX IDX_BD5C86F721C3AF14 ON shell_damage (logbook_entry_id)');
        $this->addSql('CREATE TABLE app_user (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A27597D3FE FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE logbook_entry ADD CONSTRAINT FK_7E7DB59A13AF1BA4 FOREIGN KEY (shell_id) REFERENCES shell (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE logbook_entry_member ADD CONSTRAINT FK_2CA459E21C3AF14 FOREIGN KEY (logbook_entry_id) REFERENCES logbook_entry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE logbook_entry_member ADD CONSTRAINT FK_2CA459E7597D3FE FOREIGN KEY (member_id) REFERENCES member (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE shell_damage ADD CONSTRAINT FK_BD5C86F712469DE2 FOREIGN KEY (category_id) REFERENCES shell_damage_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE shell_damage ADD CONSTRAINT FK_BD5C86F713AF1BA4 FOREIGN KEY (shell_id) REFERENCES shell (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE shell_damage ADD CONSTRAINT FK_BD5C86F721C3AF14 FOREIGN KEY (logbook_entry_id) REFERENCES logbook_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE logbook_entry DROP CONSTRAINT FK_7E7DB59A13AF1BA4');
        $this->addSql('ALTER TABLE shell_damage DROP CONSTRAINT FK_BD5C86F713AF1BA4');
        $this->addSql('ALTER TABLE shell_damage DROP CONSTRAINT FK_BD5C86F712469DE2');
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A27597D3FE');
        $this->addSql('ALTER TABLE logbook_entry_member DROP CONSTRAINT FK_2CA459E7597D3FE');
        $this->addSql('ALTER TABLE logbook_entry_member DROP CONSTRAINT FK_2CA459E21C3AF14');
        $this->addSql('ALTER TABLE shell_damage DROP CONSTRAINT FK_BD5C86F721C3AF14');
        $this->addSql('ALTER TABLE member DROP CONSTRAINT FK_70E4FA78A76ED395');
        $this->addSql('DROP SEQUENCE invitation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE shell_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE shell_damage_category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE logbook_entry_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE shell_damage_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE app_user_id_seq CASCADE');
        $this->addSql('DROP TABLE invitation');
        $this->addSql('DROP TABLE shell');
        $this->addSql('DROP TABLE shell_damage_category');
        $this->addSql('DROP TABLE member');
        $this->addSql('DROP TABLE logbook_entry');
        $this->addSql('DROP TABLE logbook_entry_member');
        $this->addSql('DROP TABLE shell_damage');
        $this->addSql('DROP TABLE app_user');
    }
}

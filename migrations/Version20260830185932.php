<?php

declare(strict_types=1);

/*
 * Copyright 2020 Mathieu Piot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830185932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store what a FIT file adds to a training (source, device, measured power, TRIMP, raw file) and to its phases (intensity, power, GPS track).';
    }

    public function up(Schema $schema): void
    {
        // Existing rows: a phase or a Concept2 id can only come from the Concept2 sync, the rest was typed in.
        $this->addSql('ALTER TABLE training ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE training SET source = \'manual\'');
        $this->addSql('UPDATE training SET source = \'concept2\' WHERE concept2_id IS NOT NULL OR EXISTS (SELECT 1 FROM training_phase WHERE training_phase.training_id = training.id)');
        $this->addSql('ALTER TABLE training ALTER source SET NOT NULL');
        $this->addSql('ALTER TABLE training ADD device VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE training ADD average_power INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training ADD trimp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training ADD fit_file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8FD1FA178C FOREIGN KEY (fit_file_id) REFERENCES uploaded_file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D5128A8FD1FA178C ON training (fit_file_id)');
        $this->addSql('ALTER TABLE training_phase ADD intensity VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD powers INTEGER[] DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD average_power INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD position_times INTEGER[] DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD latitudes INTEGER[] DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD longitudes INTEGER[] DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD altitudes INTEGER[] DEFAULT NULL');
        $this->addSql('ALTER TABLE physiology ADD resting_heart_rate INT DEFAULT NULL');
        $this->addSql('UPDATE physiology SET resting_heart_rate = 60');
        $this->addSql('ALTER TABLE physiology ALTER resting_heart_rate SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training DROP CONSTRAINT FK_D5128A8FD1FA178C');
        $this->addSql('DROP INDEX UNIQ_D5128A8FD1FA178C');
        $this->addSql('ALTER TABLE training DROP source');
        $this->addSql('ALTER TABLE training DROP device');
        $this->addSql('ALTER TABLE training DROP average_power');
        $this->addSql('ALTER TABLE training DROP trimp');
        $this->addSql('ALTER TABLE training DROP fit_file_id');
        $this->addSql('ALTER TABLE training_phase DROP intensity');
        $this->addSql('ALTER TABLE training_phase DROP powers');
        $this->addSql('ALTER TABLE training_phase DROP average_power');
        $this->addSql('ALTER TABLE training_phase DROP position_times');
        $this->addSql('ALTER TABLE training_phase DROP latitudes');
        $this->addSql('ALTER TABLE training_phase DROP longitudes');
        $this->addSql('ALTER TABLE training_phase DROP altitudes');
        $this->addSql('ALTER TABLE physiology DROP resting_heart_rate');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240401192120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Convert durations to tenthSeconds and distance to meters
        $this->addSql('UPDATE training SET duration = duration * 10');
        $this->addSql('UPDATE training SET distance = distance * 1000');
        $this->addSql('ALTER TABLE training ALTER distance TYPE INT');

        // Add new columns
        $this->addSql('ALTER TABLE training ADD stroke_rate INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training ADD average_heart_rate INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training ADD max_heart_rate INT DEFAULT NULL');

        // Define new columns from arrays
        $this->addSql('
;with stroke_rates as
(
	SELECT
	    training_id,
	    (select avg(a) from unnest(ARRAY_AGG(stroke_rates)) as a)::int as average
	FROM (
	  SELECT training_id, unnest(stroke_rates) AS stroke_rates
	  FROM training_phase
	) t
	GROUP BY training_id
)

UPDATE training SET stroke_rate = stroke_rates.average FROM stroke_rates WHERE stroke_rates.training_id = id'
        );
        $this->addSql('
;with heart_rates as
(
	SELECT
	    training_id,
	    (select avg(a) from unnest(ARRAY_AGG(heart_rates)) as a)::int as average,
	    (select max(a) from unnest(ARRAY_AGG(heart_rates)) as a)::int as max
	FROM (
	  SELECT training_id, unnest(heart_rates) AS heart_rates
	  FROM training_phase
	) t
	GROUP BY training_id
)

UPDATE training SET average_heart_rate = heart_rates.average, max_heart_rate = heart_rates.max FROM heart_rates WHERE heart_rates.training_id = id'
        );

        // Add new columns
        $this->addSql('ALTER TABLE training_phase ADD stroke_rate INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD average_heart_rate INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD max_heart_rate INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD ending_heart_rate INT DEFAULT NULL');

        // Define new columns from arrays
        $this->addSql('UPDATE training_phase SET stroke_rate = (select avg(a) from unnest(stroke_rates) as a)::int');
        $this->addSql('UPDATE training_phase SET average_heart_rate = (select avg(a) from unnest(heart_rates) as a)::int, max_heart_rate = (select max(a) from unnest(heart_rates) as a)::int WHERE heart_rates IS NOT NULL');
        $this->addSql('UPDATE training_phase SET ending_heart_rate = average_heart_rate');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training_phase DROP stroke_rate');
        $this->addSql('ALTER TABLE training_phase DROP average_heart_rate');
        $this->addSql('ALTER TABLE training_phase DROP max_heart_rate');
        $this->addSql('ALTER TABLE training_phase DROP ending_heart_rate');
        $this->addSql('ALTER TABLE training DROP stroke_rate');
        $this->addSql('ALTER TABLE training DROP average_heart_rate');
        $this->addSql('ALTER TABLE training DROP max_heart_rate');
        $this->addSql('ALTER TABLE training ALTER distance TYPE DOUBLE PRECISION');
        $this->addSql('UPDATE training SET duration = duration / 10');
        $this->addSql('UPDATE training SET distance = distance / 1000');
    }
}

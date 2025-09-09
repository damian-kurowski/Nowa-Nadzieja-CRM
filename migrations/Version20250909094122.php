<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250909094122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mdw_kandydaci (id SERIAL NOT NULL, region_id INT NOT NULL, okreg_id INT NOT NULL, imie VARCHAR(255) NOT NULL, drugie_imie VARCHAR(255) DEFAULT NULL, nazwisko VARCHAR(255) NOT NULL, pesel VARCHAR(11) NOT NULL, adres_zamieszkania TEXT NOT NULL, data_zlozenia_deklaracji DATE NOT NULL, email VARCHAR(180) NOT NULL, telefon VARCHAR(15) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B4F56AD98260155 ON mdw_kandydaci (region_id)');
        $this->addSql('CREATE INDEX IDX_B4F56ADCD91F20E ON mdw_kandydaci (okreg_id)');
        $this->addSql('ALTER TABLE mdw_kandydaci ADD CONSTRAINT FK_B4F56AD98260155 FOREIGN KEY (region_id) REFERENCES region (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mdw_kandydaci ADD CONSTRAINT FK_B4F56ADCD91F20E FOREIGN KEY (okreg_id) REFERENCES okreg (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE mdw_kandydaci DROP CONSTRAINT FK_B4F56AD98260155');
        $this->addSql('ALTER TABLE mdw_kandydaci DROP CONSTRAINT FK_B4F56ADCD91F20E');
        $this->addSql('DROP TABLE mdw_kandydaci');
    }
}

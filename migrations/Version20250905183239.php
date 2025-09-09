<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905183239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE zebranie_oddzialu ADD data_podpisu_obserwatora TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_oddzialu ADD data_podpisu_protokolanta TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_oddzialu ADD data_podpisu_prowadzacego TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_oddzialu ADD data_podpisu_przewodniczacego TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_oddzialu ADD data_podpisu_zastepcy1 TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_oddzialu ADD data_podpisu_zastepcy2 TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE zebranie_oddzialu DROP data_podpisu_obserwatora');
        $this->addSql('ALTER TABLE zebranie_oddzialu DROP data_podpisu_protokolanta');
        $this->addSql('ALTER TABLE zebranie_oddzialu DROP data_podpisu_prowadzacego');
        $this->addSql('ALTER TABLE zebranie_oddzialu DROP data_podpisu_przewodniczacego');
        $this->addSql('ALTER TABLE zebranie_oddzialu DROP data_podpisu_zastepcy1');
        $this->addSql('ALTER TABLE zebranie_oddzialu DROP data_podpisu_zastepcy2');
    }
}

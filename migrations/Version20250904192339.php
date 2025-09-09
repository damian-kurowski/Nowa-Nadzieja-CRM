<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904192339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add signature fields to zebranie_okregu table for tracking participant signatures before document generation';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE zebranie_okregu ADD obserwator_podpisal BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE zebranie_okregu ADD protokolant_podpisal BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE zebranie_okregu ADD prowadzacy_podpisal BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE zebranie_okregu ADD data_podpisu_obserwatora TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_okregu ADD data_podpisu_protokolanta TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_okregu ADD data_podpisu_prowadzacego TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE zebranie_okregu DROP obserwator_podpisal');
        $this->addSql('ALTER TABLE zebranie_okregu DROP protokolant_podpisal');
        $this->addSql('ALTER TABLE zebranie_okregu DROP prowadzacy_podpisal');
        $this->addSql('ALTER TABLE zebranie_okregu DROP data_podpisu_obserwatora');
        $this->addSql('ALTER TABLE zebranie_okregu DROP data_podpisu_protokolanta');
        $this->addSql('ALTER TABLE zebranie_okregu DROP data_podpisu_prowadzacego');
    }
}

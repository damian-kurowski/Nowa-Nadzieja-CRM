<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904201912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE zebranie_okregu ADD podpis_obserwatora TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_okregu ADD podpis_protokolanta TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_okregu ADD podpis_prowadzacego TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE zebranie_okregu ALTER obserwator_podpisal DROP DEFAULT');
        $this->addSql('ALTER TABLE zebranie_okregu ALTER protokolant_podpisal DROP DEFAULT');
        $this->addSql('ALTER TABLE zebranie_okregu ALTER prowadzacy_podpisal DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE zebranie_okregu DROP podpis_obserwatora');
        $this->addSql('ALTER TABLE zebranie_okregu DROP podpis_protokolanta');
        $this->addSql('ALTER TABLE zebranie_okregu DROP podpis_prowadzacego');
        $this->addSql('ALTER TABLE zebranie_okregu ALTER obserwator_podpisal SET DEFAULT false');
        $this->addSql('ALTER TABLE zebranie_okregu ALTER protokolant_podpisal SET DEFAULT false');
        $this->addSql('ALTER TABLE zebranie_okregu ALTER prowadzacy_podpisal SET DEFAULT false');
    }
}

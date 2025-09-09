<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250909091938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP ulica_zamieszkania');
        $this->addSql('ALTER TABLE "user" DROP nr_domu_zamieszkania');
        $this->addSql('ALTER TABLE "user" DROP nr_lokali_zamieszkania');
        $this->addSql('ALTER TABLE "user" DROP kod_pocztowy_zamieszkania');
        $this->addSql('ALTER TABLE "user" DROP miasto_zamieszkania');
        $this->addSql('ALTER TABLE "user" DROP poczta_zamieszkania');
        $this->addSql('ALTER TABLE "user" DROP ulica_korespondencyjny');
        $this->addSql('ALTER TABLE "user" DROP nr_domu_korespondencyjny');
        $this->addSql('ALTER TABLE "user" DROP nr_lokali_korespondencyjny');
        $this->addSql('ALTER TABLE "user" DROP kod_pocztowy_korespondencyjny');
        $this->addSql('ALTER TABLE "user" DROP miasto_korespondencyjne');
        $this->addSql('ALTER TABLE "user" DROP poczta_korespondencyjna');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ADD ulica_zamieszkania VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD nr_domu_zamieszkania VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD nr_lokali_zamieszkania VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD kod_pocztowy_zamieszkania VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD miasto_zamieszkania VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD poczta_zamieszkania VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ulica_korespondencyjny VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD nr_domu_korespondencyjny VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD nr_lokali_korespondencyjny VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD kod_pocztowy_korespondencyjny VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD miasto_korespondencyjne VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD poczta_korespondencyjna VARCHAR(255) DEFAULT NULL');
    }
}

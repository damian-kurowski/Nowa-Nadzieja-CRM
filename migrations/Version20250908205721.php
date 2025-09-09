<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908205721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok1_oplacenie_skladki DROP DEFAULT');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok2_wgranie_zdjecia DROP DEFAULT');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok3_wgranie_cv DROP DEFAULT');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok4_uzupelnienie_profilu DROP DEFAULT');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok5_rozmowa_prekwalifikacyjna DROP DEFAULT');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok6_opinia_rady_oddzialu DROP DEFAULT');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok7_udzial_wzebraniach DROP DEFAULT');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok8_decyzja DROP DEFAULT');
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
        $this->addSql('ALTER TABLE "user" ADD funkcje_publiczne TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD przynaleznosc TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD zgoda_rodo BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD data_zgody_rodo TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
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
        $this->addSql('ALTER TABLE "user" DROP funkcje_publiczne');
        $this->addSql('ALTER TABLE "user" DROP przynaleznosc');
        $this->addSql('ALTER TABLE "user" DROP zgoda_rodo');
        $this->addSql('ALTER TABLE "user" DROP data_zgody_rodo');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok1_oplacenie_skladki SET DEFAULT false');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok2_wgranie_zdjecia SET DEFAULT false');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok3_wgranie_cv SET DEFAULT false');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok4_uzupelnienie_profilu SET DEFAULT false');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok5_rozmowa_prekwalifikacyjna SET DEFAULT false');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok6_opinia_rady_oddzialu SET DEFAULT false');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok7_udzial_wzebraniach SET DEFAULT false');
        $this->addSql('ALTER TABLE postep_kandydata ALTER krok8_decyzja SET DEFAULT false');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908100609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE skladka_czlonkowska (id SERIAL NOT NULL, czlonek_id INT NOT NULL, zarejestrowane_przez_id INT DEFAULT NULL, import_platnosci_id INT DEFAULT NULL, rok INT NOT NULL, miesiac INT NOT NULL, kwota NUMERIC(10, 2) NOT NULL, status VARCHAR(50) NOT NULL, data_rejestracji TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, data_platnosci TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, data_waznosci_skladki TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, numer_platnosci VARCHAR(255) DEFAULT NULL, uwagi VARCHAR(255) DEFAULT NULL, sposob_platnosci VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D98F8499457C7A1 ON skladka_czlonkowska (zarejestrowane_przez_id)');
        $this->addSql('CREATE INDEX IDX_D98F8499220789C ON skladka_czlonkowska (import_platnosci_id)');
        $this->addSql('CREATE INDEX idx_skladka_czlonek ON skladka_czlonkowska (czlonek_id)');
        $this->addSql('CREATE INDEX idx_skladka_status ON skladka_czlonkowska (status)');
        $this->addSql('CREATE INDEX idx_skladka_okres ON skladka_czlonkowska (rok, miesiac)');
        $this->addSql('CREATE INDEX idx_skladka_data_platnosci ON skladka_czlonkowska (data_platnosci)');
        $this->addSql('CREATE INDEX idx_skladka_czlonek_okres ON skladka_czlonkowska (czlonek_id, rok, miesiac)');
        $this->addSql('ALTER TABLE skladka_czlonkowska ADD CONSTRAINT FK_D98F84995066ED81 FOREIGN KEY (czlonek_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE skladka_czlonkowska ADD CONSTRAINT FK_D98F8499457C7A1 FOREIGN KEY (zarejestrowane_przez_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE skladka_czlonkowska ADD CONSTRAINT FK_D98F8499220789C FOREIGN KEY (import_platnosci_id) REFERENCES import_platnosci (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE tmp_user_import');
        $this->addSql('DROP TABLE pg_temp_14.tmp_user_import');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA pg_temp_14');
        $this->addSql('CREATE TABLE tmp_user_import (imie VARCHAR(255) DEFAULT NULL, nazwisko VARCHAR(255) DEFAULT NULL, drugie_imie VARCHAR(255) DEFAULT NULL, pesel VARCHAR(255) DEFAULT NULL, plec VARCHAR(255) DEFAULT NULL, wyksztalcenie VARCHAR(255) DEFAULT NULL, ulica VARCHAR(255) DEFAULT NULL, numer_domu VARCHAR(255) DEFAULT NULL, numer_lokalu VARCHAR(255) DEFAULT NULL, kod_pocztowy VARCHAR(255) DEFAULT NULL, miasto VARCHAR(255) DEFAULT NULL, poczta VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, telefon VARCHAR(255) DEFAULT NULL, region_id INT DEFAULT NULL, okreg_id INT DEFAULT NULL, oddzial_id INT DEFAULT NULL, facebook TEXT DEFAULT NULL, instagram TEXT DEFAULT NULL, tiktok TEXT DEFAULT NULL, youtube TEXT DEFAULT NULL, x TEXT DEFAULT NULL, typ_uzytkownika VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, data_rejestracji DATE DEFAULT NULL, data_przyjecia_do_partii DATE DEFAULT NULL, data_zlozenia_deklaracji DATE DEFAULT NULL, numer_w_partii VARCHAR(255) DEFAULT NULL, dodatkowe_informacje TEXT DEFAULT NULL, notatka_wewnetrzna TEXT DEFAULT NULL, skladka_oplacona INT DEFAULT NULL)');
        $this->addSql('CREATE TABLE pg_temp_14.tmp_user_import (imie VARCHAR(255) DEFAULT NULL, nazwisko VARCHAR(255) DEFAULT NULL, drugie_imie VARCHAR(255) DEFAULT NULL, pesel VARCHAR(255) DEFAULT NULL, plec VARCHAR(255) DEFAULT NULL, wyksztalcenie VARCHAR(255) DEFAULT NULL, ulica VARCHAR(255) DEFAULT NULL, numer_domu VARCHAR(255) DEFAULT NULL, numer_lokalu VARCHAR(255) DEFAULT NULL, kod_pocztowy VARCHAR(255) DEFAULT NULL, miasto VARCHAR(255) DEFAULT NULL, poczta VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, telefon VARCHAR(255) DEFAULT NULL, region_id INT DEFAULT NULL, okreg_id INT DEFAULT NULL, oddzial_id INT DEFAULT NULL, facebook TEXT DEFAULT NULL, instagram TEXT DEFAULT NULL, tiktok TEXT DEFAULT NULL, youtube TEXT DEFAULT NULL, x TEXT DEFAULT NULL, typ_uzytkownika VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, data_rejestracji DATE DEFAULT NULL, data_przyjecia_do_partii DATE DEFAULT NULL, data_zlozenia_deklaracji DATE DEFAULT NULL, numer_w_partii VARCHAR(255) DEFAULT NULL, dodatkowe_informacje TEXT DEFAULT NULL, notatka_wewnetrzna TEXT DEFAULT NULL, skladka_oplacona INT DEFAULT NULL)');
        $this->addSql('ALTER TABLE skladka_czlonkowska DROP CONSTRAINT FK_D98F84995066ED81');
        $this->addSql('ALTER TABLE skladka_czlonkowska DROP CONSTRAINT FK_D98F8499457C7A1');
        $this->addSql('ALTER TABLE skladka_czlonkowska DROP CONSTRAINT FK_D98F8499220789C');
        $this->addSql('DROP TABLE skladka_czlonkowska');
    }
}

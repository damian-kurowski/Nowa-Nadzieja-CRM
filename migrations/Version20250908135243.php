<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908135243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postep_kandydata ADD krok1_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok2_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok3_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok4_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok5_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok6_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok7_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok8_odznaczyl_uzytkownik_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok1_oplacenie_skladki BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok1_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok2_wgranie_zdjecia BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok2_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok3_wgranie_cv BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok3_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok4_uzupelnienie_profilu BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok4_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok5_rozmowa_prekwalifikacyjna BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok5_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok6_opinia_rady_oddzialu BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok6_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok7_udzial_wzebraniach BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok7_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok8_decyzja BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE postep_kandydata ADD krok8_data_odznaczenia TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        
        // Migraj dane ze starych pól do nowych
        $this->addSql('UPDATE postep_kandydata SET krok1_oplacenie_skladki = skladka_za3_miesiace WHERE skladka_za3_miesiace = true');
        $this->addSql('UPDATE postep_kandydata SET krok3_wgranie_cv = dodanie_cv_wmiesiacu WHERE dodanie_cv_wmiesiacu = true');
        $this->addSql('UPDATE postep_kandydata SET krok7_udzial_wzebraniach = spotkanie_miesieczne WHERE spotkanie_miesieczne = true');
        $this->addSql('UPDATE postep_kandydata SET krok5_rozmowa_prekwalifikacyjna = rozmowa_zprezesem WHERE rozmowa_zprezesem = true');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F9EB5125CA FOREIGN KEY (krok1_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F9DEBC9399 FOREIGN KEY (krok2_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F97B370397 FOREIGN KEY (krok3_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F9B567FF3F FOREIGN KEY (krok4_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F910EC6F31 FOREIGN KEY (krok5_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F92501D962 FOREIGN KEY (krok6_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F9808A496C FOREIGN KEY (krok7_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postep_kandydata ADD CONSTRAINT FK_3D0A01F962D12673 FOREIGN KEY (krok8_odznaczyl_uzytkownik_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3D0A01F9EB5125CA ON postep_kandydata (krok1_odznaczyl_uzytkownik_id)');
        $this->addSql('CREATE INDEX IDX_3D0A01F9DEBC9399 ON postep_kandydata (krok2_odznaczyl_uzytkownik_id)');
        $this->addSql('CREATE INDEX IDX_3D0A01F97B370397 ON postep_kandydata (krok3_odznaczyl_uzytkownik_id)');
        $this->addSql('CREATE INDEX IDX_3D0A01F9B567FF3F ON postep_kandydata (krok4_odznaczyl_uzytkownik_id)');
        $this->addSql('CREATE INDEX IDX_3D0A01F910EC6F31 ON postep_kandydata (krok5_odznaczyl_uzytkownik_id)');
        $this->addSql('CREATE INDEX IDX_3D0A01F92501D962 ON postep_kandydata (krok6_odznaczyl_uzytkownik_id)');
        $this->addSql('CREATE INDEX IDX_3D0A01F9808A496C ON postep_kandydata (krok7_odznaczyl_uzytkownik_id)');
        $this->addSql('CREATE INDEX IDX_3D0A01F962D12673 ON postep_kandydata (krok8_odznaczyl_uzytkownik_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F9EB5125CA');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F9DEBC9399');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F97B370397');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F9B567FF3F');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F910EC6F31');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F92501D962');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F9808A496C');
        $this->addSql('ALTER TABLE postep_kandydata DROP CONSTRAINT FK_3D0A01F962D12673');
        $this->addSql('DROP INDEX IDX_3D0A01F9EB5125CA');
        $this->addSql('DROP INDEX IDX_3D0A01F9DEBC9399');
        $this->addSql('DROP INDEX IDX_3D0A01F97B370397');
        $this->addSql('DROP INDEX IDX_3D0A01F9B567FF3F');
        $this->addSql('DROP INDEX IDX_3D0A01F910EC6F31');
        $this->addSql('DROP INDEX IDX_3D0A01F92501D962');
        $this->addSql('DROP INDEX IDX_3D0A01F9808A496C');
        $this->addSql('DROP INDEX IDX_3D0A01F962D12673');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok1_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok2_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok3_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok4_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok5_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok6_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok7_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok8_odznaczyl_uzytkownik_id');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok1_oplacenie_skladki');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok1_data_odznaczenia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok2_wgranie_zdjecia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok2_data_odznaczenia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok3_wgranie_cv');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok3_data_odznaczenia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok4_uzupelnienie_profilu');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok4_data_odznaczenia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok5_rozmowa_prekwalifikacyjna');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok5_data_odznaczenia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok6_opinia_rady_oddzialu');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok6_data_odznaczenia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok7_udzial_wzebraniach');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok7_data_odznaczenia');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok8_decyzja');
        $this->addSql('ALTER TABLE postep_kandydata DROP krok8_data_odznaczenia');
    }
}

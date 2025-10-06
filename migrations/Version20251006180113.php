<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006180113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE przekaz_medialny (id SERIAL NOT NULL, autor_id INT NOT NULL, tytul VARCHAR(255) NOT NULL, tresc TEXT NOT NULL, data_wyslania TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, liczba_odbiorcow INT NOT NULL, liczba_przeczytanych INT NOT NULL, liczba_odpowiedzi INT NOT NULL, status VARCHAR(20) NOT NULL, data_utworzenia TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B74E2A2114D45BBE ON przekaz_medialny (autor_id)');
        $this->addSql('CREATE INDEX idx_przekaz_data_wyslania ON przekaz_medialny (data_wyslania)');
        $this->addSql('CREATE INDEX idx_przekaz_status ON przekaz_medialny (status)');
        $this->addSql('CREATE TABLE przekaz_odbiorca (id SERIAL NOT NULL, przekaz_id INT NOT NULL, odbiorca_id INT NOT NULL, telegram_message_id VARCHAR(100) DEFAULT NULL, data_wyslania TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, data_przeczytania TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, czy_przeczytany BOOLEAN NOT NULL, status VARCHAR(20) NOT NULL, blad TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D5E0DAA7746B1519 ON przekaz_odbiorca (przekaz_id)');
        $this->addSql('CREATE INDEX IDX_D5E0DAA7328A74B5 ON przekaz_odbiorca (odbiorca_id)');
        $this->addSql('CREATE INDEX idx_odbiorca_status ON przekaz_odbiorca (status)');
        $this->addSql('CREATE INDEX idx_odbiorca_przeczytany ON przekaz_odbiorca (czy_przeczytany)');
        $this->addSql('CREATE TABLE przekaz_odpowiedz (id SERIAL NOT NULL, przekaz_id INT NOT NULL, odbiorca_id INT NOT NULL, zweryfikowal_przez_id INT DEFAULT NULL, typ VARCHAR(20) NOT NULL, link_url VARCHAR(500) NOT NULL, data_dodania TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, zweryfikowany BOOLEAN NOT NULL, data_weryfikacji TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, uwagi TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A2DF4BEE746B1519 ON przekaz_odpowiedz (przekaz_id)');
        $this->addSql('CREATE INDEX IDX_A2DF4BEE328A74B5 ON przekaz_odpowiedz (odbiorca_id)');
        $this->addSql('CREATE INDEX IDX_A2DF4BEEB8CD7445 ON przekaz_odpowiedz (zweryfikowal_przez_id)');
        $this->addSql('CREATE INDEX idx_odpowiedz_typ ON przekaz_odpowiedz (typ)');
        $this->addSql('CREATE INDEX idx_odpowiedz_zweryfikowany ON przekaz_odpowiedz (zweryfikowany)');
        $this->addSql('CREATE INDEX idx_odpowiedz_data_dodania ON przekaz_odpowiedz (data_dodania)');
        $this->addSql('ALTER TABLE przekaz_medialny ADD CONSTRAINT FK_B74E2A2114D45BBE FOREIGN KEY (autor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE przekaz_odbiorca ADD CONSTRAINT FK_D5E0DAA7746B1519 FOREIGN KEY (przekaz_id) REFERENCES przekaz_medialny (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE przekaz_odbiorca ADD CONSTRAINT FK_D5E0DAA7328A74B5 FOREIGN KEY (odbiorca_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE przekaz_odpowiedz ADD CONSTRAINT FK_A2DF4BEE746B1519 FOREIGN KEY (przekaz_id) REFERENCES przekaz_medialny (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE przekaz_odpowiedz ADD CONSTRAINT FK_A2DF4BEE328A74B5 FOREIGN KEY (odbiorca_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE przekaz_odpowiedz ADD CONSTRAINT FK_A2DF4BEEB8CD7445 FOREIGN KEY (zweryfikowal_przez_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE przekaz_medialny DROP CONSTRAINT FK_B74E2A2114D45BBE');
        $this->addSql('ALTER TABLE przekaz_odbiorca DROP CONSTRAINT FK_D5E0DAA7746B1519');
        $this->addSql('ALTER TABLE przekaz_odbiorca DROP CONSTRAINT FK_D5E0DAA7328A74B5');
        $this->addSql('ALTER TABLE przekaz_odpowiedz DROP CONSTRAINT FK_A2DF4BEE746B1519');
        $this->addSql('ALTER TABLE przekaz_odpowiedz DROP CONSTRAINT FK_A2DF4BEE328A74B5');
        $this->addSql('ALTER TABLE przekaz_odpowiedz DROP CONSTRAINT FK_A2DF4BEEB8CD7445');
        $this->addSql('DROP TABLE przekaz_medialny');
        $this->addSql('DROP TABLE przekaz_odbiorca');
        $this->addSql('DROP TABLE przekaz_odpowiedz');
    }
}

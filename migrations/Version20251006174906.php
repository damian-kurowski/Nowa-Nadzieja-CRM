<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006174906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE opinia_rady_oddzialu_id_seq CASCADE');
        $this->addSql('CREATE TABLE verification_code (id SERIAL NOT NULL, user_id INT NOT NULL, code VARCHAR(6) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used BOOLEAN NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E821C39FA76ED395 ON verification_code (user_id)');
        $this->addSql('CREATE INDEX idx_code ON verification_code (code)');
        $this->addSql('CREATE INDEX idx_expires_at ON verification_code (expires_at)');
        $this->addSql('ALTER TABLE verification_code ADD CONSTRAINT FK_E821C39FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE opinia_rady_oddzialu DROP CONSTRAINT fk_f9255c3914d45bbe');
        $this->addSql('ALTER TABLE opinia_rady_oddzialu DROP CONSTRAINT fk_f9255c395066ed81');
        $this->addSql('DROP TABLE opinia_rady_oddzialu');
        $this->addSql('ALTER TABLE "user" DROP data_opinia_rady_oddzialu');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE opinia_rady_oddzialu_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE opinia_rady_oddzialu (id SERIAL NOT NULL, czlonek_id INT NOT NULL, autor_id INT NOT NULL, tresc_opinii TEXT NOT NULL, data_dodania TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, publiczna BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_f9255c3914d45bbe ON opinia_rady_oddzialu (autor_id)');
        $this->addSql('CREATE INDEX idx_f9255c395066ed81 ON opinia_rady_oddzialu (czlonek_id)');
        $this->addSql('ALTER TABLE opinia_rady_oddzialu ADD CONSTRAINT fk_f9255c3914d45bbe FOREIGN KEY (autor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE opinia_rady_oddzialu ADD CONSTRAINT fk_f9255c395066ed81 FOREIGN KEY (czlonek_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE verification_code DROP CONSTRAINT FK_E821C39FA76ED395');
        $this->addSql('DROP TABLE verification_code');
        $this->addSql('ALTER TABLE "user" ADD data_opinia_rady_oddzialu TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904173017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_activity_created');
        $this->addSql('DROP INDEX idx_activity_entity');
        $this->addSql('DROP INDEX idx_activity_user');
        $this->addSql('DROP INDEX idx_darczyca_typ');
        $this->addSql('DROP INDEX idx_dokument_status');
        $this->addSql('DROP INDEX idx_dokument_typ');
        $this->addSql('DROP INDEX idx_funkcja_nazwa');
        $this->addSql('DROP INDEX idx_funkcja_user');
        $this->addSql('DROP INDEX idx_user_email');
        $this->addSql('DROP INDEX idx_user_typ');
        $this->addSql('ALTER TABLE "user" ADD numer_konta_bankowego VARCHAR(34) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_zebranie_oddzialu_status');
        $this->addSql('DROP INDEX idx_zebranie_okregu_status');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE INDEX idx_zebranie_okregu_status ON zebranie_okregu (status)');
        $this->addSql('CREATE INDEX idx_darczyca_typ ON darczyca (typ_darczyny)');
        $this->addSql('CREATE INDEX idx_dokument_status ON dokument (status)');
        $this->addSql('CREATE INDEX idx_dokument_typ ON dokument (typ)');
        $this->addSql('CREATE INDEX idx_zebranie_oddzialu_status ON zebranie_oddzialu (status)');
        $this->addSql('CREATE INDEX idx_funkcja_nazwa ON funkcja (nazwa) WHERE (aktywna = true)');
        $this->addSql('CREATE INDEX idx_funkcja_user ON funkcja (user_id, aktywna)');
        $this->addSql('ALTER TABLE "user" DROP numer_konta_bankowego');
        $this->addSql('CREATE INDEX idx_user_email ON "user" (email)');
        $this->addSql('CREATE INDEX idx_user_typ ON "user" (typ_uzytkownika)');
        $this->addSql('CREATE INDEX idx_activity_created ON activity_logs (created_at)');
        $this->addSql('CREATE INDEX idx_activity_entity ON activity_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_activity_user ON activity_logs (user_id)');
    }
}

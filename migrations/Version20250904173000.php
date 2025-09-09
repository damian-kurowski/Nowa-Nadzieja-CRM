<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250904173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes for all major tables';
    }

    public function up(Schema $schema): void
    {
        // User table - most important indexes
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_email ON "user" (email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_status ON "user" (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_typ ON "user" (typ_uzytkownika)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_okreg ON "user" (okreg_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_oddzial ON "user" (oddzial_id)');
        
        // Composite indexes for common queries
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_search ON "user" (nazwisko, imie)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_typ_status ON "user" (typ_uzytkownika, status)');
        
        // Funkcja - roles and permissions
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_funkcja_user ON funkcja (user_id, aktywna)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_funkcja_nazwa ON funkcja (nazwa) WHERE aktywna = true');
        
        // Activity logs - for audit trails
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_activity_created ON activity_logs (created_at DESC)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_activity_user ON activity_logs (user_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_activity_entity ON activity_logs (entity_type, entity_id)');
        
        // Meetings - frequently queried
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zebranie_oddzialu_status ON zebranie_oddzialu (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zebranie_okregu_status ON zebranie_okregu (status)');
        
        // Documents
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_dokument_typ ON dokument (typ)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_dokument_status ON dokument (status)');
        
        // Payments (if exists)
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_platnosc_darczyca ON platnosc (darczyca_id)');
        
        // Darczyca - donors
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_darczyca_typ ON darczyca (typ_darczyny)');
    }

    public function down(Schema $schema): void
    {
        // User indexes
        $this->addSql('DROP INDEX IF EXISTS idx_user_email');
        $this->addSql('DROP INDEX IF EXISTS idx_user_status');
        $this->addSql('DROP INDEX IF EXISTS idx_user_typ');
        $this->addSql('DROP INDEX IF EXISTS idx_user_okreg');
        $this->addSql('DROP INDEX IF EXISTS idx_user_oddzial');
        $this->addSql('DROP INDEX IF EXISTS idx_user_search');
        $this->addSql('DROP INDEX IF EXISTS idx_user_typ_status');
        
        // Funkcja indexes
        $this->addSql('DROP INDEX IF EXISTS idx_funkcja_user');
        $this->addSql('DROP INDEX IF EXISTS idx_funkcja_nazwa');
        
        // Activity logs indexes
        $this->addSql('DROP INDEX IF EXISTS idx_activity_created');
        $this->addSql('DROP INDEX IF EXISTS idx_activity_user');
        $this->addSql('DROP INDEX IF EXISTS idx_activity_entity');
        
        // Meeting indexes
        $this->addSql('DROP INDEX IF EXISTS idx_zebranie_oddzialu_status');
        $this->addSql('DROP INDEX IF EXISTS idx_zebranie_okregu_status');
        
        // Document indexes
        $this->addSql('DROP INDEX IF EXISTS idx_dokument_typ');
        $this->addSql('DROP INDEX IF EXISTS idx_dokument_status');
        
        // Payment indexes
        $this->addSql('DROP INDEX IF EXISTS idx_platnosc_darczyca');
        
        // Darczyca indexes
        $this->addSql('DROP INDEX IF EXISTS idx_darczyca_typ');
    }
}
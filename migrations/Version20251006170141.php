<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006170141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Telegram connection fields to User entity';
    }

    public function up(Schema $schema): void
    {
        // Add Telegram fields to user table
        $this->addSql('ALTER TABLE "user" ADD telegram_chat_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD telegram_username VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD telegram_connected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD is_telegram_connected BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove Telegram fields from user table
        $this->addSql('ALTER TABLE "user" DROP telegram_chat_id');
        $this->addSql('ALTER TABLE "user" DROP telegram_username');
        $this->addSql('ALTER TABLE "user" DROP telegram_connected_at');
        $this->addSql('ALTER TABLE "user" DROP is_telegram_connected');
    }
}

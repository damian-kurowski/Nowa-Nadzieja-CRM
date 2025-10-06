<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251006170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add API consent fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD zgoda_api_email BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD zgoda_api_telefon BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD zgoda_api_zdjecie BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP zgoda_api_email');
        $this->addSql('ALTER TABLE "user" DROP zgoda_api_telefon');
        $this->addSql('ALTER TABLE "user" DROP zgoda_api_zdjecie');
    }
}

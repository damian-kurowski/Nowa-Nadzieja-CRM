<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904195000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add signature fields to dokument table for protokolant and prowadzacy signatures';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dokument ADD protokolant_podpisal TIMESTAMP DEFAULT NULL');
        $this->addSql('ALTER TABLE dokument ADD prowadzacy_podpisal TIMESTAMP DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dokument DROP protokolant_podpisal');
        $this->addSql('ALTER TABLE dokument DROP prowadzacy_podpisal');
    }
}
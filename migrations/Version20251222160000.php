<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251222160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add booking confirmation/reminder fields and session optimistic lock version';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD confirmation_token VARCHAR(64) DEFAULT NULL, ADD confirmation_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDE3D6F1C5 ON booking (confirmation_token)');
        $this->addSql('ALTER TABLE session ADD version INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_E00CEDDE3D6F1C5 ON booking');
        $this->addSql('ALTER TABLE booking DROP confirmation_token, DROP confirmation_sent_at, DROP confirmed_at, DROP reminder_sent_at');
        $this->addSql('ALTER TABLE session DROP version');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607033831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table with recipient FK, read_at, payload, and composite indices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (id BINARY(16) NOT NULL, type VARCHAR(255) NOT NULL, payload JSON NOT NULL, read_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, recipient_id BINARY(16) NOT NULL, INDEX IDX_BF5476CAE92F8F78 (recipient_id), INDEX idx_notification_recipient_read (recipient_id, read_at), INDEX idx_notification_recipient_created (recipient_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('DROP TABLE notification');
    }
}

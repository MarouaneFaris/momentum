<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260531191408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add WorkspaceInvitation entity with unique constraint on (workspace_id, invitee_id)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workspace_invitation (id BINARY(16) NOT NULL, role VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, workspace_id BINARY(16) NOT NULL, invitee_id BINARY(16) NOT NULL, invited_by_id BINARY(16) DEFAULT NULL, INDEX IDX_18AAE8AD82D40A1F (workspace_id), INDEX IDX_18AAE8AD7A512022 (invitee_id), INDEX IDX_18AAE8ADA7B4A7E3 (invited_by_id), UNIQUE INDEX UNIQ_WORKSPACE_INVITEE (workspace_id, invitee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE workspace_invitation ADD CONSTRAINT FK_18AAE8AD82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workspace_invitation ADD CONSTRAINT FK_18AAE8AD7A512022 FOREIGN KEY (invitee_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workspace_invitation ADD CONSTRAINT FK_18AAE8ADA7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspace_invitation DROP FOREIGN KEY FK_18AAE8AD82D40A1F');
        $this->addSql('ALTER TABLE workspace_invitation DROP FOREIGN KEY FK_18AAE8AD7A512022');
        $this->addSql('ALTER TABLE workspace_invitation DROP FOREIGN KEY FK_18AAE8ADA7B4A7E3');
        $this->addSql('DROP TABLE workspace_invitation');
    }
}

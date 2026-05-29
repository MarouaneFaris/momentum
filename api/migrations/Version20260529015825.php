<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529015825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE to user_workspace.workspace_id FK for hard workspace deletion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_workspace DROP FOREIGN KEY FK_8D748DFD82D40A1F');
        $this->addSql('ALTER TABLE user_workspace ADD CONSTRAINT FK_8D748DFD82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_workspace DROP FOREIGN KEY FK_8D748DFD82D40A1F');
        $this->addSql('ALTER TABLE user_workspace ADD CONSTRAINT FK_8D748DFD82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
    }
}

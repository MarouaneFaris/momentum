<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527211827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Workspace and UserWorkspace entities with workspace-scoped roles';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_workspace (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, user_id BINARY(16) NOT NULL, workspace_id BINARY(16) NOT NULL, INDEX IDX_8D748DFDA76ED395 (user_id), INDEX IDX_8D748DFD82D40A1F (workspace_id), UNIQUE INDEX UNIQ_USER_WORKSPACE (user_id, workspace_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workspace (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, creator_id BINARY(16) NOT NULL, INDEX IDX_8D94001961220EA6 (creator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_workspace ADD CONSTRAINT FK_8D748DFDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_workspace ADD CONSTRAINT FK_8D748DFD82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE workspace ADD CONSTRAINT FK_8D94001961220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_workspace DROP FOREIGN KEY FK_8D748DFDA76ED395');
        $this->addSql('ALTER TABLE user_workspace DROP FOREIGN KEY FK_8D748DFD82D40A1F');
        $this->addSql('ALTER TABLE workspace DROP FOREIGN KEY FK_8D94001961220EA6');
        $this->addSql('DROP TABLE user_workspace');
        $this->addSql('DROP TABLE workspace');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604171317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_project table for guest project assignment';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_project (id BINARY(16) NOT NULL, assigned_at DATETIME NOT NULL, project_id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_77BECEE4166D1F9C (project_id), INDEX IDX_77BECEE4A76ED395 (user_id), UNIQUE INDEX UNIQ_USER_PROJECT (project_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_project DROP FOREIGN KEY FK_77BECEE4166D1F9C');
        $this->addSql('ALTER TABLE user_project DROP FOREIGN KEY FK_77BECEE4A76ED395');
        $this->addSql('DROP TABLE user_project');
    }
}

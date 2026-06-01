<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260531194951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add joined_at timestamp to UserWorkspace';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_workspace ADD joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user_workspace ALTER joined_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_workspace DROP joined_at');
    }
}

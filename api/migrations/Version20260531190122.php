<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260531190122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate UserWorkspace PK from auto-increment int to UUID v7 (BINARY(16))';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_workspace CHANGE id id BINARY(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_workspace CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}

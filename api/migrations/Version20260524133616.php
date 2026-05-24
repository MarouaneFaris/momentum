<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260524133616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate User and AuthToken PKs from INT AUTO_INCREMENT to UUID v7 (BINARY(16))';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_token DROP FOREIGN KEY FK_9315F04EA76ED395');
        $this->addSql('ALTER TABLE user CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE auth_token CHANGE id id BINARY(16) NOT NULL, CHANGE user_id user_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE auth_token ADD CONSTRAINT FK_9315F04EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_token DROP FOREIGN KEY FK_9315F04EA76ED395');
        $this->addSql('ALTER TABLE auth_token CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE auth_token ADD CONSTRAINT FK_9315F04EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613223317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add color palette key to project table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE project ADD color VARCHAR(20) DEFAULT 'blue' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP color');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605121437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD office VARCHAR(255) NOT NULL, ADD phone VARCHAR(20) NOT NULL, ADD name VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD address VARCHAR(255) NOT NULL, ADD code VARCHAR(12) NOT NULL, ADD ville VARCHAR(60) NOT NULL, ADD pays VARCHAR(60) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP office, DROP phone, DROP name, DROP prenom, DROP address, DROP code, DROP ville, DROP pays
        SQL);
    }
}

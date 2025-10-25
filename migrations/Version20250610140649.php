<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610140649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE lot ADD cat_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot ADD CONSTRAINT FK_B81291BE6ADA943 FOREIGN KEY (cat_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_B81291BE6ADA943 ON lot (cat_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE lot DROP FOREIGN KEY FK_B81291BE6ADA943
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_B81291BE6ADA943 ON lot
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot DROP cat_id
        SQL);
    }
}

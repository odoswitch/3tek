<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610141435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE lot_type (lot_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_18F2C969A8CBA5F7 (lot_id), INDEX IDX_18F2C969C54C8C93 (type_id), PRIMARY KEY(lot_id, type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE type (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type ADD CONSTRAINT FK_18F2C969A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type ADD CONSTRAINT FK_18F2C969C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type DROP FOREIGN KEY FK_18F2C969A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type DROP FOREIGN KEY FK_18F2C969C54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lot_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE type
        SQL);
    }
}

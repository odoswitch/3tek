<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251025184407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE file_attente (id INT AUTO_INCREMENT NOT NULL, lot_id INT NOT NULL, user_id INT NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', statut VARCHAR(50) NOT NULL, notified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_4F10E0F2A8CBA5F7 (lot_id), INDEX IDX_4F10E0F2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente ADD CONSTRAINT FK_4F10E0F2A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente ADD CONSTRAINT FK_4F10E0F2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot ADD reserve_par_id INT DEFAULT NULL, ADD statut VARCHAR(50) NOT NULL, ADD reserve_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot ADD CONSTRAINT FK_B81291B70E4F16E FOREIGN KEY (reserve_par_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B81291B70E4F16E ON lot (reserve_par_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente DROP FOREIGN KEY FK_4F10E0F2A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente DROP FOREIGN KEY FK_4F10E0F2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE file_attente
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot DROP FOREIGN KEY FK_B81291B70E4F16E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B81291B70E4F16E ON lot
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot DROP reserve_par_id, DROP statut, DROP reserve_at
        SQL);
    }
}

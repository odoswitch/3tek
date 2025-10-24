<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251023201938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user_type (user_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_F65F1BE0A76ED395 (user_id), INDEX IDX_F65F1BE0C54C8C93 (type_id), PRIMARY KEY(user_id, type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_type ADD CONSTRAINT FK_F65F1BE0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_type ADD CONSTRAINT FK_F65F1BE0C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot ADD quantite INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_8D93D649A8CBA5F7 ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP lot_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_type DROP FOREIGN KEY FK_F65F1BE0A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_type DROP FOREIGN KEY FK_F65F1BE0C54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD lot_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES type (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649A8CBA5F7 ON `user` (lot_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot DROP quantite
        SQL);
    }
}

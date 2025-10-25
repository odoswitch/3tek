<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250622165546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lot (id INT AUTO_INCREMENT NOT NULL, cat_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, UNIQUE INDEX UNIQ_B81291BE6ADA943 (cat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lot_type (lot_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_18F2C969A8CBA5F7 (lot_id), INDEX IDX_18F2C969C54C8C93 (type_id), PRIMARY KEY(lot_id, type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, lot_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT '(DC2Type:json)', password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, code VARCHAR(20) NOT NULL, ville VARCHAR(255) NOT NULL, pays VARCHAR(255) NOT NULL, office VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, phone VARCHAR(60) NOT NULL, UNIQUE INDEX UNIQ_8D93D649A8CBA5F7 (lot_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_category (user_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_E6C1FDC1A76ED395 (user_id), INDEX IDX_E6C1FDC112469DE2 (category_id), PRIMARY KEY(user_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot ADD CONSTRAINT FK_B81291BE6ADA943 FOREIGN KEY (cat_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type ADD CONSTRAINT FK_18F2C969A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type ADD CONSTRAINT FK_18F2C969C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES type (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_category ADD CONSTRAINT FK_E6C1FDC1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_category ADD CONSTRAINT FK_E6C1FDC112469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE lot DROP FOREIGN KEY FK_B81291BE6ADA943
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type DROP FOREIGN KEY FK_18F2C969A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_type DROP FOREIGN KEY FK_18F2C969C54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_category DROP FOREIGN KEY FK_E6C1FDC1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_category DROP FOREIGN KEY FK_E6C1FDC112469DE2
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lot
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lot_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reset_password_request
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `user`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}

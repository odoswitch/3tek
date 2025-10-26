<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251026105545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE commande CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION NOT NULL, CHANGE prix_total prix_total DOUBLE PRECISION NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE validated_at validated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE commentaire commentaire LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6EEAA67DA76ED395 ON commande (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6EEAA67DA8CBA5F7 ON commande (lot_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_log CHANGE error_message error_message LONGTEXT DEFAULT NULL, CHANGE sent_at sent_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE context context LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favori CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCA8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EF85A2CCA76ED395 ON favori (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EF85A2CCA8CBA5F7 ON favori (lot_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente ADD expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD expired_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE notified_at notified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente RENAME INDEX fk_file_attente_lot TO IDX_4F10E0F2A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente RENAME INDEX fk_file_attente_user TO IDX_4F10E0F2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot CHANGE quantite quantite INT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE reserve_at reserve_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot ADD CONSTRAINT FK_B81291B70E4F16E FOREIGN KEY (reserve_par_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B81291B70E4F16E ON lot (reserve_par_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot RENAME INDEX fk_b81291be6ada943 TO IDX_B81291BE6ADA943
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_image DROP image_path, DROP created_at, CHANGE position position INT NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_image ADD CONSTRAINT FK_B3756951A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B3756951A8CBA5F7 ON lot_image (lot_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_24CC0DF2A76ED395 ON panier (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_24CC0DF2A8CBA5F7 ON panier (lot_id)
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
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D649C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D649C54C8C93 ON user (type_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCA8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_EF85A2CCA76ED395 ON favori
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_EF85A2CCA8CBA5F7 ON favori
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favori CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente DROP expires_at, DROP expired_at, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE statut statut VARCHAR(50) DEFAULT 'en_attente', CHANGE notified_at notified_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente RENAME INDEX idx_4f10e0f2a76ed395 TO FK_file_attente_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file_attente RENAME INDEX idx_4f10e0f2a8cba5f7 TO FK_file_attente_lot
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot DROP FOREIGN KEY FK_B81291B70E4F16E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B81291B70E4F16E ON lot
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot CHANGE quantite quantite INT DEFAULT 0, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT 'disponible', CHANGE reserve_at reserve_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot RENAME INDEX idx_b81291be6ada943 TO FK_B81291BE6ADA943
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_image DROP FOREIGN KEY FK_B3756951A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B3756951A8CBA5F7 ON lot_image
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_image ADD image_path VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE position position INT DEFAULT 0, CHANGE updated_at updated_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_log CHANGE error_message error_message TEXT DEFAULT NULL, CHANGE sent_at sent_at DATETIME NOT NULL, CHANGE context context TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_24CC0DF2A76ED395 ON panier
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_24CC0DF2A8CBA5F7 ON panier
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649C54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D649C54C8C93 ON `user`
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
            ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA8CBA5F7
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_6EEAA67DA76ED395 ON commande
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_6EEAA67DA8CBA5F7 ON commande
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande CHANGE prix_unitaire prix_unitaire NUMERIC(10, 2) NOT NULL, CHANGE prix_total prix_total NUMERIC(10, 2) NOT NULL, CHANGE statut statut VARCHAR(50) DEFAULT 'en_attente', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE validated_at validated_at DATETIME DEFAULT NULL, CHANGE commentaire commentaire TEXT DEFAULT NULL
        SQL);
    }
}

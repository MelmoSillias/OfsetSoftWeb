<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519180405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE account_transaction (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, invoice_id INT DEFAULT NULL, client_id INT DEFAULT NULL, commission_id INT DEFAULT NULL, validation_user_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', income NUMERIC(10, 2) NOT NULL, outcome NUMERIC(10, 2) NOT NULL, account_type VARCHAR(30) NOT NULL, balance_value NUMERIC(10, 2) NOT NULL, status VARCHAR(30) NOT NULL, payment_method VARCHAR(255) NOT NULL, payment_ref VARCHAR(255) NOT NULL, validate_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A370F9D2A76ED395 (user_id), INDEX IDX_A370F9D22989F1FD (invoice_id), INDEX IDX_A370F9D219EB6921 (client_id), UNIQUE INDEX UNIQ_A370F9D2202D1EB2 (commission_id), INDEX IDX_A370F9D2B6EB8E9B (validation_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, company_name VARCHAR(255) NOT NULL, delegate VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, committee VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commission (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', taken_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_1C6501582989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', amount NUMERIC(10, 2) NOT NULL, remain NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_9065174419EB6921 (client_id), INDEX IDX_90651744A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE invoice_item (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, describ VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, quantity INT NOT NULL, INDEX IDX_1DDE477B2989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction ADD CONSTRAINT FK_A370F9D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction ADD CONSTRAINT FK_A370F9D22989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction ADD CONSTRAINT FK_A370F9D219EB6921 FOREIGN KEY (client_id) REFERENCES client (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction ADD CONSTRAINT FK_A370F9D2202D1EB2 FOREIGN KEY (commission_id) REFERENCES commission (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction ADD CONSTRAINT FK_A370F9D2B6EB8E9B FOREIGN KEY (validation_user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commission ADD CONSTRAINT FK_1C6501582989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice ADD CONSTRAINT FK_9065174419EB6921 FOREIGN KEY (client_id) REFERENCES client (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice ADD CONSTRAINT FK_90651744A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction DROP FOREIGN KEY FK_A370F9D2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction DROP FOREIGN KEY FK_A370F9D22989F1FD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction DROP FOREIGN KEY FK_A370F9D219EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction DROP FOREIGN KEY FK_A370F9D2202D1EB2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE account_transaction DROP FOREIGN KEY FK_A370F9D2B6EB8E9B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commission DROP FOREIGN KEY FK_1C6501582989F1FD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice DROP FOREIGN KEY FK_9065174419EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice DROP FOREIGN KEY FK_90651744A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B2989F1FD
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE account_transaction
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE client
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE invoice
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE invoice_item
        SQL);
    }
}

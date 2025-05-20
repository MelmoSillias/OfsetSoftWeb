<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519181921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE renewable_invoice (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', update_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', start_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', end_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', next_date DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', state VARCHAR(10) NOT NULL, INDEX IDX_BDA43F4D19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE renewable_invoice_item (id INT AUTO_INCREMENT NOT NULL, renewable_invoice_id INT NOT NULL, describ VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, INDEX IDX_C50F0443976696D3 (renewable_invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE renewable_invoice ADD CONSTRAINT FK_BDA43F4D19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE renewable_invoice_item ADD CONSTRAINT FK_C50F0443976696D3 FOREIGN KEY (renewable_invoice_id) REFERENCES renewable_invoice (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE renewable_invoice DROP FOREIGN KEY FK_BDA43F4D19EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE renewable_invoice_item DROP FOREIGN KEY FK_C50F0443976696D3
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE renewable_invoice
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE renewable_invoice_item
        SQL);
    }
}

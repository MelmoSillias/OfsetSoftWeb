<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250521150521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE archiving (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, archivist_id INT NOT NULL, archiving_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', warehouse_office VARCHAR(255) NOT NULL, archiving_coordinate VARCHAR(255) NOT NULL, archiving_notes VARCHAR(512) DEFAULT NULL, INDEX IDX_A84564EA93CB796C (file_id), INDEX IDX_A84564EA1FCF2010 (archivist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE case_docs (id INT AUTO_INCREMENT NOT NULL, primary_recipient_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, reference VARCHAR(12) NOT NULL, sender_name VARCHAR(255) NOT NULL, sender_contact VARCHAR(255) DEFAULT NULL, date_reception DATE NOT NULL COMMENT '(DC2Type:date_immutable)', mode_transmission VARCHAR(255) NOT NULL, urgency VARCHAR(20) NOT NULL, sender VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, general_observations VARCHAR(512) NOT NULL, INDEX IDX_D04FF1047334D3AE (primary_recipient_id), INDEX IDX_D04FF1047E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, folder_id INT NOT NULL, description VARCHAR(255) NOT NULL, number_of_copies INT NOT NULL, number_of_pages INT NOT NULL, document_date DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', supporting_documents VARCHAR(255) NOT NULL, attached_files LONGTEXT NOT NULL COMMENT '(DC2Type:array)', notes VARCHAR(255) NOT NULL, INDEX IDX_D8698A76162CB942 (folder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE processing_file (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, user_id INT NOT NULL, processing_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', observations VARCHAR(512) NOT NULL, action VARCHAR(10) NOT NULL, INDEX IDX_4F56E61D93CB796C (file_id), INDEX IDX_4F56E61DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE transfer_file (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, transfer_responsible_id INT NOT NULL, tranfer_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', reason VARCHAR(255) NOT NULL, INDEX IDX_B46A1A7293CB796C (file_id), INDEX IDX_B46A1A72FDFA87F4 (transfer_responsible_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE archiving ADD CONSTRAINT FK_A84564EA93CB796C FOREIGN KEY (file_id) REFERENCES case_docs (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE archiving ADD CONSTRAINT FK_A84564EA1FCF2010 FOREIGN KEY (archivist_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE case_docs ADD CONSTRAINT FK_D04FF1047334D3AE FOREIGN KEY (primary_recipient_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE case_docs ADD CONSTRAINT FK_D04FF1047E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document ADD CONSTRAINT FK_D8698A76162CB942 FOREIGN KEY (folder_id) REFERENCES case_docs (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE processing_file ADD CONSTRAINT FK_4F56E61D93CB796C FOREIGN KEY (file_id) REFERENCES case_docs (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE processing_file ADD CONSTRAINT FK_4F56E61DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transfer_file ADD CONSTRAINT FK_B46A1A7293CB796C FOREIGN KEY (file_id) REFERENCES case_docs (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transfer_file ADD CONSTRAINT FK_B46A1A72FDFA87F4 FOREIGN KEY (transfer_responsible_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE archiving DROP FOREIGN KEY FK_A84564EA93CB796C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE archiving DROP FOREIGN KEY FK_A84564EA1FCF2010
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE case_docs DROP FOREIGN KEY FK_D04FF1047334D3AE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE case_docs DROP FOREIGN KEY FK_D04FF1047E3C61F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document DROP FOREIGN KEY FK_D8698A76162CB942
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE processing_file DROP FOREIGN KEY FK_4F56E61D93CB796C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE processing_file DROP FOREIGN KEY FK_4F56E61DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transfer_file DROP FOREIGN KEY FK_B46A1A7293CB796C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transfer_file DROP FOREIGN KEY FK_B46A1A72FDFA87F4
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE archiving
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE case_docs
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE document
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE processing_file
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE transfer_file
        SQL);
    }
}

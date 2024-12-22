<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241222150226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, usd_rate NUMERIC(10, 0) DEFAULT NULL, is_current TINYINT(1) DEFAULT NULL, code VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, currency_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ref VARCHAR(255) NOT NULL, amount NUMERIC(10, 0) NOT NULL, description VARCHAR(255) DEFAULT NULL, resource_id INT DEFAULT NULL, phone_number VARCHAR(255) NOT NULL, order_number VARCHAR(255) DEFAULT NULL, paid TINYINT(1) DEFAULT NULL, INDEX IDX_6D28840D38248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D38248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE line ADD currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE line ADD CONSTRAINT FK_D114B4F638248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_D114B4F638248176 ON line (currency_id)');
        $this->addSql('ALTER TABLE subscription_plan ADD currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription_plan ADD CONSTRAINT FK_EA664B6338248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_EA664B6338248176 ON subscription_plan (currency_id)');
        $this->addSql('ALTER TABLE transaction ADD currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D138248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_723705D138248176 ON transaction (currency_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE line DROP FOREIGN KEY FK_D114B4F638248176');
        $this->addSql('ALTER TABLE subscription_plan DROP FOREIGN KEY FK_EA664B6338248176');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D138248176');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D38248176');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP INDEX IDX_D114B4F638248176 ON line');
        $this->addSql('ALTER TABLE line DROP currency_id');
        $this->addSql('DROP INDEX IDX_EA664B6338248176 ON subscription_plan');
        $this->addSql('ALTER TABLE subscription_plan DROP currency_id');
        $this->addSql('DROP INDEX IDX_723705D138248176 ON transaction');
        $this->addSql('ALTER TABLE transaction DROP currency_id');
    }
}

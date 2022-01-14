<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220114201100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE upload ADD upload_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE upload ADD CONSTRAINT FK_17BDE61F83BA6D1B FOREIGN KEY (upload_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_17BDE61F83BA6D1B ON upload (upload_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE upload DROP CONSTRAINT FK_17BDE61F83BA6D1B');
        $this->addSql('DROP INDEX IDX_17BDE61F83BA6D1B');
        $this->addSql('ALTER TABLE upload DROP upload_by_id');
    }
}

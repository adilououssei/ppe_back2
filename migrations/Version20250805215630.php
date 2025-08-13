<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805215630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE creneau (id INT AUTO_INCREMENT NOT NULL, disponibilite_id INT NOT NULL, debut TIME NOT NULL, fin TIME NOT NULL, type VARCHAR(255) DEFAULT NULL, INDEX IDX_F9668B5F2B9D6493 (disponibilite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE disponibilite (id INT AUTO_INCREMENT NOT NULL, docteur_id INT NOT NULL, date DATE NOT NULL, INDEX IDX_2CBACE2FCF22540A (docteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE creneau ADD CONSTRAINT FK_F9668B5F2B9D6493 FOREIGN KEY (disponibilite_id) REFERENCES disponibilite (id)');
        $this->addSql('ALTER TABLE disponibilite ADD CONSTRAINT FK_2CBACE2FCF22540A FOREIGN KEY (docteur_id) REFERENCES docteur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE creneau DROP FOREIGN KEY FK_F9668B5F2B9D6493');
        $this->addSql('ALTER TABLE disponibilite DROP FOREIGN KEY FK_2CBACE2FCF22540A');
        $this->addSql('DROP TABLE creneau');
        $this->addSql('DROP TABLE disponibilite');
    }
}

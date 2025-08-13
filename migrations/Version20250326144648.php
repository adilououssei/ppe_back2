<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250326144648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification ADD docteur_id INT DEFAULT NULL, ADD patient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CACF22540A FOREIGN KEY (docteur_id) REFERENCES docteur (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA6B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)');
        $this->addSql('CREATE INDEX IDX_BF5476CACF22540A ON notification (docteur_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA6B899279 ON notification (patient_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CACF22540A');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA6B899279');
        $this->addSql('DROP INDEX IDX_BF5476CACF22540A ON notification');
        $this->addSql('DROP INDEX IDX_BF5476CA6B899279 ON notification');
        $this->addSql('ALTER TABLE notification DROP docteur_id, DROP patient_id');
    }
}

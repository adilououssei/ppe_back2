<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407012639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE docteur ADD user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE docteur ADD CONSTRAINT FK_83A7A439A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_83A7A439A76ED395 ON docteur (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE docteur DROP FOREIGN KEY FK_83A7A439A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_83A7A439A76ED395 ON docteur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE docteur DROP user_id
        SQL);
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519103124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE "like" (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, article_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AC6340B3FB88E14F ON "like" (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AC6340B37294869C ON "like" (article_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "like".created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" ADD CONSTRAINT FK_AC6340B3FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" ADD CONSTRAINT FK_AC6340B37294869C FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" DROP CONSTRAINT FK_AC6340B3FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" DROP CONSTRAINT FK_AC6340B37294869C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "like"
        SQL);
    }
}

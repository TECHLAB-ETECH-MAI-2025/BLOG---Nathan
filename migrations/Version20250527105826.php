<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250527105826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE conversion_devise (id SERIAL NOT NULL, utilisateur_id INT DEFAULT NULL, montant NUMERIC(15, 2) NOT NULL, devise_source VARCHAR(3) NOT NULL, devise_cible VARCHAR(3) NOT NULL, taux_change NUMERIC(10, 6) NOT NULL, montant_converti NUMERIC(15, 2) NOT NULL, frais_bancaires NUMERIC(10, 2) DEFAULT NULL, montant_final NUMERIC(15, 2) DEFAULT NULL, date_transaction DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_134DBF5FFB88E14F ON conversion_devise (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ADD CONSTRAINT FK_134DBF5FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise DROP CONSTRAINT FK_134DBF5FFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE conversion_devise
        SQL);
    }
}

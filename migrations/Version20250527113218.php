<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250527113218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise DROP CONSTRAINT fk_134dbf5ffb88e14f
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_134dbf5ffb88e14f
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ADD url_api VARCHAR(500) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ADD fallback BOOLEAN NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ADD donnees_api TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise DROP utilisateur_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise DROP montant_final
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER taux_change TYPE NUMERIC(15, 6)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER frais_bancaires TYPE NUMERIC(5, 2)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER frais_bancaires SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER date_transaction TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise RENAME COLUMN montant TO montant_original
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise RENAME COLUMN created_at TO date_creation
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ADD utilisateur_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ADD montant_final NUMERIC(15, 2) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise DROP url_api
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise DROP fallback
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise DROP donnees_api
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER taux_change TYPE NUMERIC(10, 6)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER frais_bancaires TYPE NUMERIC(10, 2)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER frais_bancaires DROP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ALTER date_transaction TYPE DATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise RENAME COLUMN montant_original TO montant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise RENAME COLUMN date_creation TO created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion_devise ADD CONSTRAINT fk_134dbf5ffb88e14f FOREIGN KEY (utilisateur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_134dbf5ffb88e14f ON conversion_devise (utilisateur_id)
        SQL);
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261023213028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE abonnement (
              id INT AUTO_INCREMENT NOT NULL,
              offre VARCHAR(255) NOT NULL,
              montant_mensuel INT NOT NULL,
              debut DATE NOT NULL,
              fin_engagement DATE NOT NULL,
              stripe_customer_id VARCHAR(64) DEFAULT NULL,
              resilie_le DATE DEFAULT NULL,
              prospect_id INT NOT NULL,
              UNIQUE INDEX UNIQ_351268BBD182060A (prospect_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contact (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(255) DEFAULT NULL,
              email VARCHAR(180) DEFAULT NULL,
              email_verifie TINYINT NOT NULL,
              telephone VARCHAR(32) DEFAULT NULL,
              role VARCHAR(100) DEFAULT NULL,
              prospect_id INT NOT NULL,
              INDEX IDX_4C62E638D182060A (prospect_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE devis (
              id INT AUTO_INCREMENT NOT NULL,
              reference VARCHAR(50) DEFAULT NULL,
              offre VARCHAR(255) NOT NULL,
              mensualite INT NOT NULL,
              frais_mise_en_ligne INT NOT NULL,
              sans_engagement TINYINT NOT NULL,
              envoye_le DATE NOT NULL,
              statut VARCHAR(16) NOT NULL,
              prospect_id INT NOT NULL,
              INDEX IDX_8B27C52BD182060A (prospect_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE interaction (
              id INT AUTO_INCREMENT NOT NULL,
              date DATETIME NOT NULL,
              type VARCHAR(255) NOT NULL,
              canal VARCHAR(255) DEFAULT NULL,
              resume LONGTEXT DEFAULT NULL,
              prospect_id INT NOT NULL,
              INDEX IDX_378DFDA7AA9E377A (date),
              INDEX IDX_378DFDA7D182060A (prospect_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE modele_message (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(120) NOT NULL,
              objet VARCHAR(255) DEFAULT NULL,
              corps LONGTEXT NOT NULL,
              UNIQUE INDEX UNIQ_B330CAAD6C6E55B5 (nom),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE prescripteur (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(255) NOT NULL,
              type VARCHAR(32) NOT NULL,
              commission_pourcent INT DEFAULT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE prospect (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(255) NOT NULL,
              secteur VARCHAR(255) DEFAULT NULL,
              source VARCHAR(255) DEFAULT NULL,
              site_actuel VARCHAR(255) NOT NULL,
              url_site VARCHAR(255) DEFAULT NULL,
              priorite VARCHAR(255) NOT NULL,
              offre_visee VARCHAR(255) NOT NULL,
              canal_prefere VARCHAR(255) DEFAULT NULL,
              statut VARCHAR(32) NOT NULL,
              besoin_identifie TINYINT NOT NULL,
              capacite_budget TINYINT NOT NULL,
              intention_affirmee TINYINT NOT NULL,
              nb_relances INT NOT NULL,
              dernier_contact DATE DEFAULT NULL,
              opt_out TINYINT NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              cree_le DATETIME NOT NULL,
              prescripteur_id INT DEFAULT NULL,
              INDEX IDX_C9CE8C7DE564F0BF (statut),
              INDEX IDX_C9CE8C7DD486E642 (prescripteur_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tache (
              id INT AUTO_INCREMENT NOT NULL,
              type VARCHAR(255) NOT NULL,
              echeance DATE NOT NULL,
              description LONGTEXT DEFAULT NULL,
              generee TINYINT NOT NULL,
              faite_le DATETIME DEFAULT NULL,
              annulee_le DATETIME DEFAULT NULL,
              prospect_id INT NOT NULL,
              INDEX IDX_9387207540D9893B (echeance),
              INDEX IDX_93872075D182060A (prospect_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              abonnement
            ADD
              CONSTRAINT FK_351268BBD182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contact
            ADD
              CONSTRAINT FK_4C62E638D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              devis
            ADD
              CONSTRAINT FK_8B27C52BD182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interaction
            ADD
              CONSTRAINT FK_378DFDA7D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prospect
            ADD
              CONSTRAINT FK_C9CE8C7DD486E642 FOREIGN KEY (prescripteur_id) REFERENCES prescripteur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              tache
            ADD
              CONSTRAINT FK_93872075D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBD182060A');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638D182060A');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52BD182060A');
        $this->addSql('ALTER TABLE interaction DROP FOREIGN KEY FK_378DFDA7D182060A');
        $this->addSql('ALTER TABLE prospect DROP FOREIGN KEY FK_C9CE8C7DD486E642');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY FK_93872075D182060A');
        $this->addSql('DROP TABLE abonnement');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE devis');
        $this->addSql('DROP TABLE interaction');
        $this->addSql('DROP TABLE modele_message');
        $this->addSql('DROP TABLE prescripteur');
        $this->addSql('DROP TABLE prospect');
        $this->addSql('DROP TABLE tache');
    }
}

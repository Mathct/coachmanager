<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la gestion des compositions de match (football).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE composition (id INT AUTO_INCREMENT NOT NULL, rencontre_id INT NOT NULL, formation VARCHAR(20) NOT NULL, is_validated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_DD5A75A89A93D823 (rencontre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE composition_player (id INT AUTO_INCREMENT NOT NULL, composition_id INT NOT NULL, player_id INT NOT NULL, status VARCHAR(20) NOT NULL, position_code VARCHAR(20) DEFAULT NULL, jersey_number INT DEFAULT NULL, INDEX IDX_ECF5103EA2E97047 (composition_id), INDEX IDX_ECF5103E99E6F5DF (player_id), UNIQUE INDEX uniq_comp_player (composition_id, player_id), UNIQUE INDEX uniq_comp_jersey (composition_id, jersey_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE composition ADD CONSTRAINT FK_DD5A75A89A93D823 FOREIGN KEY (rencontre_id) REFERENCES rencontre (id)');
        $this->addSql('ALTER TABLE composition_player ADD CONSTRAINT FK_ECF5103EA2E97047 FOREIGN KEY (composition_id) REFERENCES composition (id)');
        $this->addSql('ALTER TABLE composition_player ADD CONSTRAINT FK_ECF5103E99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE composition_player DROP FOREIGN KEY FK_ECF5103EA2E97047');
        $this->addSql('ALTER TABLE composition_player DROP FOREIGN KEY FK_ECF5103E99E6F5DF');
        $this->addSql('ALTER TABLE composition DROP FOREIGN KEY FK_DD5A75A89A93D823');
        $this->addSql('DROP TABLE composition_player');
        $this->addSql('DROP TABLE composition');
    }
}

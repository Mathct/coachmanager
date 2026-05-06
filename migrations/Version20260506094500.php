<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506094500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le flag is_placed pour le placement manuel.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE composition_player ADD is_placed TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE composition_player DROP is_placed');
    }
}

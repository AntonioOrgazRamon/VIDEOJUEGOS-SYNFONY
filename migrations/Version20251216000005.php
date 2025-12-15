<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para crear tabla game_invitations
 */
final class Version20251216000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_invitations table for game invitations between friends';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE game_invitations (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            inviter_id BIGINT UNSIGNED NOT NULL,
            invited_user_id BIGINT UNSIGNED NOT NULL,
            game_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) DEFAULT \'pending\' NOT NULL,
            room_id BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            expires_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX idx_game_invitations_invited (invited_user_id),
            INDEX idx_game_invitations_status (status),
            FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (invited_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE game_invitations');
    }
}



<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para crear tabla game_rooms
 */
final class Version20251216000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_rooms table for multiplayer game sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE game_rooms (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            game_id BIGINT UNSIGNED NOT NULL,
            player1_id BIGINT UNSIGNED NOT NULL,
            player2_id BIGINT UNSIGNED DEFAULT NULL,
            status VARCHAR(20) DEFAULT \'waiting\' NOT NULL,
            current_turn BIGINT UNSIGNED DEFAULT NULL,
            game_state JSON DEFAULT NULL,
            winner_id BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            started_at DATETIME DEFAULT NULL,
            finished_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX idx_game_rooms_game (game_id),
            INDEX idx_game_rooms_status (status),
            INDEX idx_game_rooms_created (created_at),
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            FOREIGN KEY (player1_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (player2_id) REFERENCES users(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE game_rooms');
    }
}



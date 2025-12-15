<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para crear tabla user_ban_history
 */
final class Version20251216000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_ban_history table for tracking ban/kick history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_ban_history (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            action_type VARCHAR(20) NOT NULL,
            message TEXT DEFAULT NULL,
            performed_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_ban_history_user (user_id),
            INDEX idx_ban_history_type (action_type),
            INDEX idx_ban_history_created (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_ban_history');
    }
}



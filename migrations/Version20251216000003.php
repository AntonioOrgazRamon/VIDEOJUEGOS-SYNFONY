<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para crear tabla friendships
 */
final class Version20251216000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create friendships table for managing user friendships';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE friendships (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            user1_id BIGINT UNSIGNED NOT NULL,
            user2_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) DEFAULT \'pending\' NOT NULL,
            requested_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            UNIQUE KEY uq_friendships_pair (user1_id, user2_id),
            INDEX idx_friendships_user1 (user1_id),
            INDEX idx_friendships_user2 (user2_id),
            INDEX idx_friendships_status (status),
            FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE friendships');
    }
}



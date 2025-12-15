<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para crear tabla ban_appeals
 */
final class Version20251216000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ban_appeals table for ban appeal system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ban_appeals (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) DEFAULT \'pending\' NOT NULL,
            admin_response TEXT DEFAULT NULL,
            reviewed_by BIGINT UNSIGNED DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_ban_appeal_user (user_id),
            INDEX idx_ban_appeal_status (status),
            INDEX idx_ban_appeal_created (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ban_appeals');
    }
}



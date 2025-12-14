<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251214002313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Eliminar la restricción unique del username para permitir nombres duplicados
        $this->addSql('ALTER TABLE users DROP INDEX uq_users_username');
        
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE games CHANGE icon icon VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE password_resets CHANGE used_at used_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE user_game_likes CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE user_game_stats CHANGE last_played_at last_played_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE user_presence CHANGE state state VARCHAR(20) DEFAULT \'active\' NOT NULL, CHANGE current_page current_page VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user_scores CHANGE played_at played_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE extra_data extra_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user_sessions CHANGE ip ip VARCHAR(45) DEFAULT NULL, CHANGE user_agent user_agent VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE last_activity_at last_activity_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL, CHANGE profile_image profile_image VARCHAR(255) DEFAULT \'images/default_profile.png\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'active\' NOT NULL, CHANGE status_message status_message VARCHAR(120) DEFAULT NULL, CHANGE last_seen_at last_seen_at DATETIME DEFAULT NULL, CHANGE visibility visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, CHANGE theme_mode theme_mode VARCHAR(5) DEFAULT \'light\' NOT NULL, CHANGE language language VARCHAR(2) DEFAULT \'es\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE games CHANGE icon icon VARCHAR(255) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE password_resets CHANGE used_at used_at DATETIME DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE profile_image profile_image VARCHAR(255) DEFAULT \'\'\'images/default_profile.png\'\'\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'\'\'active\'\'\' NOT NULL, CHANGE status_message status_message VARCHAR(120) DEFAULT \'NULL\', CHANGE last_seen_at last_seen_at DATETIME DEFAULT \'NULL\', CHANGE visibility visibility VARCHAR(20) DEFAULT \'\'\'public\'\'\' NOT NULL, CHANGE theme_mode theme_mode ENUM(\'light\', \'dark\') DEFAULT \'\'\'light\'\'\' NOT NULL, CHANGE language language ENUM(\'es\', \'en\') DEFAULT \'\'\'es\'\'\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE user_game_likes CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE user_game_stats CHANGE last_played_at last_played_at DATETIME DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE user_presence CHANGE state state VARCHAR(20) DEFAULT \'\'\'active\'\'\' NOT NULL, CHANGE current_page current_page VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE user_scores CHANGE played_at played_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE extra_data extra_data LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE user_sessions CHANGE ip ip VARCHAR(45) DEFAULT \'NULL\', CHANGE user_agent user_agent VARCHAR(255) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE last_activity_at last_activity_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT \'NULL\'');
        
        // Restaurar la restricción unique del username
        $this->addSql('ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)');
    }
}

-- =========================================
-- DB + charset
-- =========================================
CREATE DATABASE IF NOT EXISTS game_platform
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE game_platform;

-- =========================================
-- 1) USERS
-- =========================================
CREATE TABLE users (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(255) NOT NULL,
  password        VARCHAR(255) NOT NULL,
  username        VARCHAR(60)  NOT NULL,
  roles           JSON         NOT NULL,
  profile_image   VARCHAR(255) NOT NULL DEFAULT 'images/default_profile.png',
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  status          ENUM('active','away','offline') NOT NULL DEFAULT 'active',
  status_message  VARCHAR(120) NULL,
  last_seen_at    DATETIME     NULL,
  visibility      ENUM('public','friends','hidden') NOT NULL DEFAULT 'public',
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_last_seen (last_seen_at),
  KEY idx_users_status (status),
  KEY idx_users_visibility (visibility)
) ENGINE=InnoDB;

-- =========================================
-- 2) GAMES
-- =========================================
CREATE TABLE games (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(100) NOT NULL,
  description TEXT         NULL,
  icon        VARCHAR(255) NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_games_slug (slug),
  KEY idx_games_active (is_active)
) ENGINE=InnoDB;

-- =========================================
-- 3) USER_SCORES (historial + ranking)
-- =========================================
CREATE TABLE user_scores (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  game_id    BIGINT UNSIGNED NOT NULL,
  score      INT            NOT NULL,
  played_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  duration   INT            NULL,
  level      INT            NULL,
  extra_data JSON           NULL,
  CONSTRAINT fk_user_scores_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_user_scores_game
    FOREIGN KEY (game_id) REFERENCES games(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  KEY idx_scores_game_score (game_id, score),
  KEY idx_scores_user_game (user_id, game_id),
  KEY idx_scores_played_at (played_at)
) ENGINE=InnoDB;

-- =========================================
-- 4) PASSWORD_RESETS (código 5 dígitos)
-- =========================================
CREATE TABLE password_resets (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  code_hash  VARCHAR(255)    NOT NULL,
  expires_at DATETIME        NOT NULL,
  attempts   INT             NOT NULL DEFAULT 0,
  used_at    DATETIME        NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  KEY idx_pwreset_user_created (user_id, created_at),
  KEY idx_pwreset_expires (expires_at),
  KEY idx_pwreset_used (used_at)
) ENGINE=InnoDB;

-- =========================================
-- 5) USER_SESSIONS (conectados reales)
-- =========================================
CREATE TABLE user_sessions (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          BIGINT UNSIGNED NOT NULL,
  session_token    VARCHAR(255)    NOT NULL,
  ip               VARCHAR(45)     NULL,
  user_agent       VARCHAR(255)    NULL,
  created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_activity_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at         DATETIME        NULL,
  is_valid         TINYINT(1)      NOT NULL DEFAULT 1,
  CONSTRAINT fk_user_sessions_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  UNIQUE KEY uq_user_sessions_token (session_token),
  KEY idx_sessions_user (user_id),
  KEY idx_sessions_activity (last_activity_at),
  KEY idx_sessions_open (ended_at, is_valid)
) ENGINE=InnoDB;

-- =========================================
-- 6) USER_PRESENCE (qué hace / dónde está)
-- =========================================
CREATE TABLE user_presence (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  state           ENUM('active','away','offline') NOT NULL DEFAULT 'active',
  current_page    VARCHAR(255) NULL,
  current_game_id BIGINT UNSIGNED NULL,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_presence_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_user_presence_game
    FOREIGN KEY (current_game_id) REFERENCES games(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  UNIQUE KEY uq_presence_user (user_id),
  KEY idx_presence_state (state),
  KEY idx_presence_game (current_game_id)
) ENGINE=InnoDB;

-- =========================================
-- 7) USER_GAME_LIKES (favoritos / me gusta)
-- =========================================
CREATE TABLE user_game_likes (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  game_id    BIGINT UNSIGNED NOT NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_game_likes_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_user_game_likes_game
    FOREIGN KEY (game_id) REFERENCES games(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  UNIQUE KEY uq_like_user_game (user_id, game_id),
  KEY idx_like_game (game_id),
  KEY idx_like_user (user_id),
  KEY idx_like_created (created_at)
) ENGINE=InnoDB;

-- =========================================
-- 8) USER_GAME_STATS (agregados por usuario/juego)
-- =========================================
CREATE TABLE user_game_stats (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        BIGINT UNSIGNED NOT NULL,
  game_id        BIGINT UNSIGNED NOT NULL,
  plays_count    INT            NOT NULL DEFAULT 0,
  best_score     INT            NOT NULL DEFAULT 0,
  last_played_at DATETIME       NULL,
  total_duration INT            NULL,
  updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_game_stats_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_user_game_stats_game
    FOREIGN KEY (game_id) REFERENCES games(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  UNIQUE KEY uq_stats_user_game (user_id, game_id),
  KEY idx_stats_user (user_id),
  KEY idx_stats_game (game_id),
  KEY idx_stats_best (game_id, best_score),
  KEY idx_stats_plays (user_id, plays_count)
) ENGINE=InnoDB;

-- =========================================
-- Seed: 4 juegos
-- =========================================
INSERT INTO games (name, slug, description, icon, is_active) VALUES
('Snake',        'snake',        'Clásico Snake: crece y no te choques.',              'icons/snake.png',        1),
('Buscaminas',   'buscaminas',   'Encuentra las minas sin explotar.',                  'icons/buscaminas.png',   1),
('4 en raya',    '4-en-raya',    'Consigue 4 en línea antes que tu rival.',            'icons/4-en-raya.png',    1),
('Bombas',       'bombas',       'Juego de bombas: evita peligros y suma puntos.',     'icons/bombas.png',       1);


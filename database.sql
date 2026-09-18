CREATE DATABASE IF NOT EXISTS tournament_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tournament_system;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS standings;
DROP TABLE IF EXISTS match_events;
DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS participants;
DROP TABLE IF EXISTS tournaments;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL,
  role ENUM('super_admin','admin','judge') NOT NULL DEFAULT 'judge',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE tournaments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  location VARCHAR(180) NOT NULL,
  type ENUM('single_elimination','double_elimination','round_robin','group_stage') NOT NULL DEFAULT 'single_elimination',
  maximum_participants INT UNSIGNED NOT NULL DEFAULT 32,
  status ENUM('upcoming','live','finished') NOT NULL DEFAULT 'upcoming',
  champion_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tournaments_status (status)
) ENGINE=InnoDB;

CREATE TABLE participants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(140) NOT NULL,
  team_name VARCHAR(140) NULL,
  institution VARCHAR(180) NULL,
  logo VARCHAR(255) NULL,
  seed INT UNSIGNED NULL,
  status ENUM('active','inactive','disqualified') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_participant_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  UNIQUE KEY uq_tournament_seed (tournament_id, seed),
  INDEX idx_participants_name (name), INDEX idx_participants_status (status)
) ENGINE=InnoDB;

ALTER TABLE tournaments ADD CONSTRAINT fk_tournament_champion FOREIGN KEY (champion_id) REFERENCES participants(id) ON DELETE SET NULL;

CREATE TABLE matches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id BIGINT UNSIGNED NOT NULL,
  round VARCHAR(80) NOT NULL,
  round_number INT UNSIGNED NOT NULL,
  match_number INT UNSIGNED NOT NULL,
  participant1_id BIGINT UNSIGNED NULL,
  participant2_id BIGINT UNSIGNED NULL,
  score1 INT UNSIGNED NOT NULL DEFAULT 0,
  score2 INT UNSIGNED NOT NULL DEFAULT 0,
  winner_id BIGINT UNSIGNED NULL,
  next_match_id BIGINT UNSIGNED NULL,
  next_match_slot TINYINT UNSIGNED NULL,
  assigned_judge_id BIGINT UNSIGNED NULL,
  scheduled_at DATETIME NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  court VARCHAR(80) NULL DEFAULT 'Court 1',
  status ENUM('scheduled','live','paused','finished') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_match_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  CONSTRAINT fk_match_p1 FOREIGN KEY (participant1_id) REFERENCES participants(id) ON DELETE SET NULL,
  CONSTRAINT fk_match_p2 FOREIGN KEY (participant2_id) REFERENCES participants(id) ON DELETE SET NULL,
  CONSTRAINT fk_match_winner FOREIGN KEY (winner_id) REFERENCES participants(id) ON DELETE SET NULL,
  CONSTRAINT fk_match_next FOREIGN KEY (next_match_id) REFERENCES matches(id) ON DELETE SET NULL,
  CONSTRAINT fk_match_judge FOREIGN KEY (assigned_judge_id) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_match_round (tournament_id, round_number, match_number),
  INDEX idx_matches_status (status), INDEX idx_matches_schedule (scheduled_at)
) ENGINE=InnoDB;

CREATE TABLE match_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  match_id BIGINT UNSIGNED NOT NULL,
  participant_id BIGINT UNSIGNED NULL,
  event_type ENUM('score','start','pause','finish','reset') NOT NULL,
  score_change INT NOT NULL DEFAULT 0,
  score_after INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
  CONSTRAINT fk_event_participant FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE SET NULL,
  INDEX idx_events_match_time (match_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE standings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id BIGINT UNSIGNED NOT NULL,
  participant_id BIGINT UNSIGNED NOT NULL,
  played INT UNSIGNED NOT NULL DEFAULT 0,
  wins INT UNSIGNED NOT NULL DEFAULT 0,
  losses INT UNSIGNED NOT NULL DEFAULT 0,
  score_for INT UNSIGNED NOT NULL DEFAULT 0,
  score_against INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_standing_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  CONSTRAINT fk_standing_participant FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
  UNIQUE KEY uq_standing_participant (tournament_id, participant_id)
) ENGINE=InnoDB;

CREATE TABLE settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  description TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_entity (entity_type, entity_id), INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

-- SHA-256 bootstrap hash for admin123. On first successful login the app upgrades it
-- automatically to PHP password_hash() using the current PASSWORD_DEFAULT algorithm.
INSERT INTO users (username,password,name,role) VALUES
('admin','sha256$240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9','Tournament Admin','super_admin');

INSERT INTO tournaments (id,name,description,start_date,end_date,location,type,maximum_participants,status) VALUES
(1,'Competition 2026','Kejuaraan nasional dengan sistem single elimination dan live scoring.','2026-09-16','2026-09-18','Nusantara Sports Arena','single_elimination',16,'live');

INSERT INTO participants (id,tournament_id,name,team_name,institution,seed,status) VALUES
(1,1,'Garuda Team','Garuda Team','Universitas Garuda',1,'active'),
(2,1,'Rajawali Team','Rajawali Team','Akademi Rajawali',2,'active'),
(3,1,'Nusantara Team','Nusantara Team','Nusantara Institute',3,'active'),
(4,1,'Merapi Team','Merapi Team','Merapi College',4,'active'),
(5,1,'Borneo Team','Borneo Team','Borneo University',5,'active'),
(6,1,'Papua Team','Papua Team','Papua Academy',6,'active'),
(7,1,'Komodo Team','Komodo Team','Komodo Institute',7,'active'),
(8,1,'Cendrawasih Team','Cendrawasih Team','Cendrawasih School',8,'active');

-- Complete persisted bracket tree: four Quarter Finals feed two Semi Finals and one Final.
INSERT INTO matches (id,tournament_id,round,round_number,match_number,participant1_id,participant2_id,score1,score2,scheduled_at,court,status) VALUES
(1,1,'Quarter Final',1,1,1,8,2,1,'2026-09-16 09:00:00','Court 1','live'),
(2,1,'Quarter Final',1,2,4,5,0,0,'2026-09-16 10:00:00','Court 1','scheduled'),
(3,1,'Quarter Final',1,3,2,7,0,0,'2026-09-16 11:00:00','Court 2','scheduled'),
(4,1,'Quarter Final',1,4,3,6,0,0,'2026-09-16 12:00:00','Court 2','scheduled'),
(5,1,'Semi Final',2,1,NULL,NULL,0,0,'2026-09-17 10:00:00','Main Arena','scheduled'),
(6,1,'Semi Final',2,2,NULL,NULL,0,0,'2026-09-17 13:00:00','Main Arena','scheduled'),
(7,1,'Final',3,1,NULL,NULL,0,0,'2026-09-18 15:00:00','Main Arena','scheduled');
UPDATE matches SET next_match_id=5,next_match_slot=1 WHERE id=1;
UPDATE matches SET next_match_id=5,next_match_slot=2 WHERE id=2;
UPDATE matches SET next_match_id=6,next_match_slot=1 WHERE id=3;
UPDATE matches SET next_match_id=6,next_match_slot=2 WHERE id=4;
UPDATE matches SET next_match_id=7,next_match_slot=1 WHERE id=5;
UPDATE matches SET next_match_id=7,next_match_slot=2 WHERE id=6;

INSERT INTO match_events (match_id,participant_id,event_type,score_change,score_after,created_at) VALUES
(1,1,'start',0,0,'2026-09-16 09:00:00'),
(1,1,'score',1,1,'2026-09-16 09:05:00'),
(1,8,'score',1,1,'2026-09-16 09:08:00'),
(1,1,'score',1,2,'2026-09-16 09:13:00');

INSERT INTO settings (setting_key,setting_value) VALUES ('site_name','ArenaFlow'),('polling_interval','3000'),('primary_color','#38bdf8');

-- ================================================================
-- study.sql — Base de données simplifiée (sans tags, sans fiches_cours)
-- Tables : utilisateurs, matieres, fiches, cours
-- ================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0; -- On désactive les vérifications pour pouvoir DROP dans n'importe quel ordre

-- ================================================================
-- TABLE : utilisateurs
-- Contient les comptes des utilisateurs.
-- Toutes les autres tables dépendent de celle-ci via user_id.
-- ================================================================
DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
  `id`                  INT          NOT NULL AUTO_INCREMENT,
  `nom`                 VARCHAR(100) NOT NULL,
  `email`               VARCHAR(150) NOT NULL,
  `mot_de_passe`        VARCHAR(255) NOT NULL,     -- Hash bcrypt, jamais le vrai mot de passe
  `nb_connexions`       INT          DEFAULT 0,    -- Incrémenté à chaque connexion réussie
  `date_inscription`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion`  DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)              -- Un email ne peut pas être utilisé deux fois
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE : matieres
-- Les matières sont utilisées dans les fiches et cours.
-- Relation : un utilisateur peut avoir plusieurs matières.
-- ON DELETE SET NULL dans fiches/cours : si on supprime une matière,
--   les fiches et cours ne sont pas supprimés, juste matiere_id = NULL
-- ================================================================
DROP TABLE IF EXISTS `matieres`;
CREATE TABLE `matieres` (
  `id`      INT          NOT NULL AUTO_INCREMENT,
  `user_id` INT          NOT NULL,                 -- Clé étrangère → utilisateurs.id
  `nom`     VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_matieres_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `utilisateurs`(`id`)
    ON DELETE CASCADE                              -- Supprime les matières si l'utilisateur est supprimé
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE : fiches
-- Fiches de révision de l'utilisateur.
-- matiere_id peut être NULL si aucune matière choisie ou si matière supprimée.
-- ================================================================
DROP TABLE IF EXISTS `fiches`;
CREATE TABLE `fiches` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `user_id`    INT          NOT NULL,              -- Clé étrangère → utilisateurs.id
  `matiere_id` INT          DEFAULT NULL,          -- Clé étrangère → matieres.id (optionnel)
  `titre`      VARCHAR(255) DEFAULT NULL,
  `contenu`    TEXT,
  `difficulte` VARCHAR(50)  DEFAULT NULL,          -- Facile / Moyen / Difficile
  `favori`     INT          DEFAULT 0,             -- 0 = non favori, 1 = favori
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_fiches_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `utilisateurs`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_fiches_matiere`
    FOREIGN KEY (`matiere_id`)
    REFERENCES `matieres`(`id`)
    ON DELETE SET NULL                             -- Si matière supprimée, la fiche reste mais matiere_id = NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE : cours
-- Résumés de cours de l'utilisateur.
-- matiere_id peut être NULL si aucune matière choisie ou si matière supprimée.
-- ================================================================
DROP TABLE IF EXISTS `cours`;
CREATE TABLE `cours` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `user_id`    INT          NOT NULL,              -- Clé étrangère → utilisateurs.id
  `matiere_id` INT          DEFAULT NULL,          -- Clé étrangère → matieres.id (optionnel)
  `titre`      VARCHAR(255) DEFAULT NULL,
  `contenu`    TEXT,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cours_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `utilisateurs`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_cours_matiere`
    FOREIGN KEY (`matiere_id`)
    REFERENCES `matieres`(`id`)
    ON DELETE SET NULL                             -- Si matière supprimée, le cours reste mais matiere_id = NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1; -- On réactive les vérifications

-- ================================================================
-- RÉSUMÉ DES RELATIONS :
--
--  utilisateurs ──(1,N)── matieres
--  utilisateurs ──(1,N)── fiches
--  utilisateurs ──(1,N)── cours
--  matieres     ──(0,N)── fiches         (SET NULL si supprimée)
--  matieres     ──(0,N)── cours          (SET NULL si supprimée)
-- ================================================================

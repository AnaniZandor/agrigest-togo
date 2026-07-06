-- =====================================================
-- AGRIGEST TOGO - Script de creation de la base de donnees
-- Version finale - Cas C (UTILISATEUR central)
-- =====================================================

DROP DATABASE IF EXISTS agrigest_togo;
CREATE DATABASE agrigest_togo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrigest_togo;

-- ---------------------------------------------------------
-- Table UTILISATEUR (table centrale - tous les roles)
-- id_utilisateur articule selon le role :
--   Admin       : ADM-001
--   Responsable : RSP-COP001-001
--   Agriculteur : AGR-COP001-001
-- ---------------------------------------------------------
CREATE TABLE UTILISATEUR (
    id_utilisateur VARCHAR(20) PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'responsable', 'agriculteur') NOT NULL
);

-- ---------------------------------------------------------
-- Table COOPERATIVE
-- ---------------------------------------------------------
CREATE TABLE COOPERATIVE (
    id_coop VARCHAR(15) PRIMARY KEY,
    nom_coop VARCHAR(100) NOT NULL,
    localisation_coop VARCHAR(150) NOT NULL
);

-- ---------------------------------------------------------
-- Table RESPONSABLE (infos metier du responsable)
-- id_responsable = meme valeur que id_utilisateur (RSP-...)
-- ---------------------------------------------------------
CREATE TABLE RESPONSABLE (
    id_responsable VARCHAR(20) PRIMARY KEY,
    id_coop VARCHAR(15) NOT NULL,
    CONSTRAINT fk_responsable_utilisateur FOREIGN KEY (id_responsable)
        REFERENCES UTILISATEUR(id_utilisateur)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_responsable_coop FOREIGN KEY (id_coop)
        REFERENCES COOPERATIVE(id_coop)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ---------------------------------------------------------
-- Table AGRICULTEUR (infos metier de l'agriculteur)
-- id_agri = meme valeur que id_utilisateur (AGR-...)
-- ---------------------------------------------------------
CREATE TABLE AGRICULTEUR (
    id_agri VARCHAR(20) PRIMARY KEY,
    contact_agri VARCHAR(20) NOT NULL,
    id_coop VARCHAR(15) NOT NULL,
    CONSTRAINT fk_agriculteur_utilisateur FOREIGN KEY (id_agri)
        REFERENCES UTILISATEUR(id_utilisateur)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_agriculteur_coop FOREIGN KEY (id_coop)
        REFERENCES COOPERATIVE(id_coop)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ---------------------------------------------------------
-- Table ZONE_AGROECOLOGIQUE
-- ---------------------------------------------------------
CREATE TABLE ZONE_AGROECOLOGIQUE (
    id_zone VARCHAR(15) PRIMARY KEY,
    nom_zone VARCHAR(50) NOT NULL UNIQUE
);

-- ---------------------------------------------------------
-- Table PARCELLE
-- ---------------------------------------------------------
CREATE TABLE PARCELLE (
    id_parcelle VARCHAR(20) PRIMARY KEY,
    nom_parcelle VARCHAR(50) NOT NULL,
    localisation_parcelle VARCHAR(100) NOT NULL,
    superficie DECIMAL(8,2) NOT NULL,
    id_zone VARCHAR(15) NOT NULL,
    id_agri VARCHAR(20) NOT NULL,
    CONSTRAINT chk_superficie CHECK (superficie > 0),
    CONSTRAINT fk_parcelle_zone FOREIGN KEY (id_zone)
        REFERENCES ZONE_AGROECOLOGIQUE(id_zone)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_parcelle_agri FOREIGN KEY (id_agri)
        REFERENCES AGRICULTEUR(id_agri)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ---------------------------------------------------------
-- Table CULTURE
-- ---------------------------------------------------------
CREATE TABLE CULTURE (
    id_culture VARCHAR(15) PRIMARY KEY,
    nom_culture VARCHAR(50) NOT NULL UNIQUE,
    duree_cycle DECIMAL(6,2) NOT NULL
);

-- ---------------------------------------------------------
-- Table SAISON
-- id_saison articule : SAI-2025P (P=pluvieuse, S=seche)
-- ---------------------------------------------------------
CREATE TABLE SAISON (
    id_saison VARCHAR(15) PRIMARY KEY,
    libelle_saison VARCHAR(50) NOT NULL,
    date_debut_saison DATE NOT NULL,
    date_fin_saison DATE NOT NULL,
    CONSTRAINT chk_dates_saison CHECK (date_fin_saison > date_debut_saison)
);

-- ---------------------------------------------------------
-- Table INTRANT
-- ---------------------------------------------------------
CREATE TABLE INTRANT (
    id_intrant VARCHAR(15) PRIMARY KEY,
    nom_intrant VARCHAR(50) NOT NULL,
    type_intrant ENUM('engrais', 'semence', 'eau') NOT NULL,
    unite_mesure VARCHAR(10) NOT NULL
);

-- ---------------------------------------------------------
-- Table PLANTATION (entite porteuse - association ternaire)
-- ---------------------------------------------------------
CREATE TABLE PLANTATION (
    id_plantation VARCHAR(20) PRIMARY KEY,
    date_semis DATE NOT NULL,
    id_parcelle VARCHAR(20) NOT NULL,
    id_culture VARCHAR(15) NOT NULL,
    id_saison VARCHAR(15) NOT NULL,
    CONSTRAINT fk_plantation_parcelle FOREIGN KEY (id_parcelle)
        REFERENCES PARCELLE(id_parcelle)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_plantation_culture FOREIGN KEY (id_culture)
        REFERENCES CULTURE(id_culture)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_plantation_saison FOREIGN KEY (id_saison)
        REFERENCES SAISON(id_saison)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ---------------------------------------------------------
-- Table RECOLTE
-- ---------------------------------------------------------
CREATE TABLE RECOLTE (
    id_recolte VARCHAR(20) PRIMARY KEY,
    date_recolte DATE NOT NULL,
    rendement DECIMAL(10,2) NOT NULL,
    id_plantation VARCHAR(20) NOT NULL,
    CONSTRAINT chk_rendement CHECK (rendement > 0),
    CONSTRAINT fk_recolte_plantation FOREIGN KEY (id_plantation)
        REFERENCES PLANTATION(id_plantation)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ---------------------------------------------------------
-- Table UTILISER (association porteuse n,n)
-- Cle primaire composite (id_plantation + id_intrant)
-- ---------------------------------------------------------
CREATE TABLE UTILISER (
    id_plantation VARCHAR(20) NOT NULL,
    id_intrant VARCHAR(15) NOT NULL,
    quantite_utilisee DECIMAL(8,2) NOT NULL,
    date_utilisation DATE NOT NULL,
    PRIMARY KEY (id_plantation, id_intrant),
    CONSTRAINT chk_quantite CHECK (quantite_utilisee > 0),
    CONSTRAINT fk_utiliser_plantation FOREIGN KEY (id_plantation)
        REFERENCES PLANTATION(id_plantation)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_utiliser_intrant FOREIGN KEY (id_intrant)
        REFERENCES INTRANT(id_intrant)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
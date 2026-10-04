-- Schéma complet, exécuté automatiquement par Database.php quand la base
-- SQLite est vide (fichier manquant ou nouvellement créé par PDO), pour que
-- le site ne plante jamais faute de tables. Reflète l'état de moncv.sqlite
-- au 2026-08-26 (voir aussi migrations/001_* et 002_* pour l'historique).

CREATE TABLE IF NOT EXISTS cv_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    template_key TEXT NOT NULL DEFAULT 'default',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    is_primary INTEGER NOT NULL DEFAULT 0,
    ats_friendly INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS address
(
    id      integer
        constraint address_pk
            primary key autoincrement,
    line    VARCHAR(100),
    zipcode integer,
    city    VARCHAR(100),
    country VARCHAR(100),
    cv_version_id INTEGER
);

CREATE TABLE IF NOT EXISTS hobbies
(
    id        integer
        constraint hobbies_pk
            primary key autoincrement,
    label     VARCHAR(30),
    displayed INT default 1,
    cv_version_id INTEGER
);

CREATE TABLE IF NOT EXISTS information
(
    id         integer
        constraint information_pk
            primary key autoincrement,
    firstname  VARCHAR(255),
    lastname   VARCHAR(255),
    email      VARCHAR(255),
    phone      VARCHAR(20),
    image_path VARCHAR(100),
    job_title VARCHAR(255),
    birthdate VARCHAR(20),
    linkedin_url VARCHAR(255),
    cv_version_id INTEGER
);

CREATE TABLE IF NOT EXISTS formations
(
    id          integer
        constraint formations_pk
            primary key autoincrement,
    school      VARCHAR(255),
    degree      VARCHAR(255),
    city        VARCHAR(100),
    start_date  VARCHAR(20),
    end_date    VARCHAR(20),
    description TEXT,
    displayed   INT default 1,
    cv_version_id INTEGER
);

CREATE TABLE IF NOT EXISTS experiences
(
    id          integer
        constraint experiences_pk
            primary key autoincrement,
    company     VARCHAR(255),
    position    VARCHAR(255),
    city        VARCHAR(100),
    start_date  VARCHAR(20),
    end_date    VARCHAR(20),
    description TEXT,
    sort_order  INT default 0,
    displayed   INT default 1,
    tech_stack TEXT,
    page_break_before INT default 0,
    cv_version_id INTEGER
);

CREATE TABLE IF NOT EXISTS certifications
(
    id            integer
        constraint certifications_pk
            primary key autoincrement,
    title         VARCHAR(255),
    exam_version  VARCHAR(100),
    year          VARCHAR(10),
    sort_order    INT default 0,
    displayed     INT default 1,
    cv_version_id INTEGER
);

CREATE TABLE IF NOT EXISTS skills
(
    id         integer
        constraint skills_pk
            primary key autoincrement,
    category   VARCHAR(100),
    items      TEXT,
    sort_order INT default 0,
    cv_version_id INTEGER
);

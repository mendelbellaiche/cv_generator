-- Run ONCE: sqlite3 moncv.sqlite < migrations/001_add_cv_versions.sql
-- Idempotent for the CREATE TABLE; the ALTER TABLE statements will error if
-- re-run (SQLite has no "ADD COLUMN IF NOT EXISTS") -- that's fine, it's a
-- signal the migration already applied. Back up moncv.sqlite before running.

CREATE TABLE IF NOT EXISTS cv_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    template_key TEXT NOT NULL DEFAULT 'default',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

ALTER TABLE information    ADD COLUMN cv_version_id INTEGER;
ALTER TABLE address        ADD COLUMN cv_version_id INTEGER;
ALTER TABLE hobbies        ADD COLUMN cv_version_id INTEGER;
ALTER TABLE formations     ADD COLUMN cv_version_id INTEGER;
ALTER TABLE experiences    ADD COLUMN cv_version_id INTEGER;
ALTER TABLE certifications ADD COLUMN cv_version_id INTEGER;
ALTER TABLE skills         ADD COLUMN cv_version_id INTEGER;

-- Le CV existant devient la version 1
INSERT INTO cv_versions (id, name, template_key) VALUES (1, 'CV original', 'default');

UPDATE information    SET cv_version_id = 1 WHERE cv_version_id IS NULL;
UPDATE address        SET cv_version_id = 1 WHERE cv_version_id IS NULL;
UPDATE hobbies        SET cv_version_id = 1 WHERE cv_version_id IS NULL;
UPDATE formations     SET cv_version_id = 1 WHERE cv_version_id IS NULL;
UPDATE experiences    SET cv_version_id = 1 WHERE cv_version_id IS NULL;
UPDATE certifications SET cv_version_id = 1 WHERE cv_version_id IS NULL;
UPDATE skills         SET cv_version_id = 1 WHERE cv_version_id IS NULL;

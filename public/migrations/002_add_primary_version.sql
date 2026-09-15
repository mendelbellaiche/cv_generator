-- Run ONCE: sqlite3 moncv.sqlite < migrations/002_add_primary_version.sql
-- Ajoute un flag "version principale" (celle affichée par "Voir mon CV" sur la
-- page d'accueil) puisque jusqu'ici c'était toujours la plus ancienne version.

ALTER TABLE cv_versions ADD COLUMN is_primary INTEGER NOT NULL DEFAULT 0;

UPDATE cv_versions SET is_primary = 1 WHERE id = (SELECT MIN(id) FROM cv_versions);

-- Run ONCE: sqlite3 moncv.sqlite < migrations/003_add_ats_friendly.sql
-- Ajoute un flag par version pour activer un rendu simplifié optimisé pour
-- les logiciels de tri automatique de candidatures (ATS) : sans icônes
-- image, sans mise en page en colonnes, sans tableaux.

ALTER TABLE cv_versions ADD COLUMN ats_friendly INTEGER NOT NULL DEFAULT 0;

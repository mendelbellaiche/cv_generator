-- Run ONCE: sqlite3 moncv.sqlite < migrations/004_add_certification_badge.sql
-- Permet d'associer une image de badge (facultative) à une certification.

ALTER TABLE certifications ADD COLUMN badge_path VARCHAR(255);

-- Run ONCE: sqlite3 moncv.sqlite < migrations/005_add_summary.sql
-- Ajoute un court résumé (2-3 lignes) affiché sous le titre dans la sidebar.

ALTER TABLE information ADD COLUMN summary TEXT;

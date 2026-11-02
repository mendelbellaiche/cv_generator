-- Run ONCE: sqlite3 moncv.sqlite < migrations/006_add_github_url.sql
-- Ajoute un lien GitHub (facultatif) affiché dans la sidebar.

ALTER TABLE information ADD COLUMN github_url VARCHAR(255);

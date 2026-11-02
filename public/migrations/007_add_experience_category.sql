-- Run ONCE: sqlite3 moncv.sqlite < migrations/007_add_experience_category.sql
-- Sépare les expériences en deux sections du CV :
-- 'pro' = Expériences professionnelles, 'freelance' = Freelance & projets personnels.

ALTER TABLE experiences ADD COLUMN category VARCHAR(20) NOT NULL DEFAULT 'pro';

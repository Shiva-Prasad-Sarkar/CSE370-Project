-- ══════════════════════════════════════════════════════════════
-- Run this in phpMyAdmin BEFORE uploading the updated PHP files
-- Adds review approval system to customers_reviews table
-- ══════════════════════════════════════════════════════════════

-- Step 1: Drop existing primary key, then add ID as the new auto-increment PK
--         plus the approved flag and timestamp columns
ALTER TABLE customers_reviews
  DROP PRIMARY KEY,
  ADD COLUMN ID          int(11)    NOT NULL AUTO_INCREMENT FIRST,
  ADD PRIMARY KEY (ID),
  ADD COLUMN approved    tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN created_at  timestamp  NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Step 2: All EXISTING reviews were already public — approve them
UPDATE customers_reviews SET approved = 1;

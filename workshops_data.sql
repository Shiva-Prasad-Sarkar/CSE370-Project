-- ══════════════════════════════════════════════════════════════
-- Sample workshops for EcoGrow — Bangladesh perspective
-- Run in phpMyAdmin after uploading the updated PHP files
-- Branch IDs are looked up by name so order doesn't matter
-- ══════════════════════════════════════════════════════════════

-- 1. Free — Indoor Plant Care Basics (Dhanmondi, Dhaka)
INSERT INTO workshops (Topic, Subject, Date, Type, CreatedBy, Points, Price)
VALUES (
    'Indoor Plant Care Basics',
    'Learn how to keep your indoor plants healthy — watering schedules, sunlight needs, soil types, and common disease prevention for popular Bangladeshi homes.',
    '2026-07-05',
    'Free',
    1, NULL, NULL
);
INSERT INTO workshops_branches (WorkshopID, BranchID)
SELECT LAST_INSERT_ID(), ID FROM branches WHERE Name = 'Dhanmondi Garden Centre';

-- 2. Paid — Rooftop Garden Design (Gulshan + Uttara)
INSERT INTO workshops (Topic, Subject, Date, Type, CreatedBy, Points, Price)
VALUES (
    'Rooftop Garden Design',
    'A hands-on session on designing productive rooftop gardens suited to Dhaka''s climate — container selection, drainage, wind protection, and seasonal planting calendars.',
    '2026-07-19',
    'Paid',
    1, 150, 499.00
);
INSERT INTO workshops_branches (WorkshopID, BranchID)
SELECT LAST_INSERT_ID(), ID FROM branches WHERE Name IN ('Gulshan Green House', 'Uttara Plant Studio');

-- 3. Paid — Organic Composting & Soil Health (Mirpur)
INSERT INTO workshops (Topic, Subject, Date, Type, CreatedBy, Points, Price)
VALUES (
    'Organic Composting & Soil Health',
    'Discover how to turn kitchen waste into rich compost at home. Covers vermicomposting, bokashi, and organic fertilisers commonly available in Bangladesh.',
    '2026-08-02',
    'Paid',
    1, 100, 299.00
);
INSERT INTO workshops_branches (WorkshopID, BranchID)
SELECT LAST_INSERT_ID(), ID FROM branches WHERE Name = 'Mirpur EcoGrow Hub';

-- 4. Free — Medicinal Plants of Bangladesh (Sylhet)
INSERT INTO workshops (Topic, Subject, Date, Type, CreatedBy, Points, Price)
VALUES (
    'Medicinal Plants of Bangladesh',
    'An educational session exploring native medicinal herbs — Neem, Tulsi, Thankuni, Aloe Vera — their cultivation, traditional uses, and home remedies relevant to rural and urban Bangladesh.',
    '2026-08-22',
    'Free',
    1, NULL, NULL
);
INSERT INTO workshops_branches (WorkshopID, BranchID)
SELECT LAST_INSERT_ID(), ID FROM branches WHERE Name = 'Sylhet Green House';

-- 5. Paid — Tropical Flowering Plants Workshop (Chattogram)
INSERT INTO workshops (Topic, Subject, Date, Type, CreatedBy, Points, Price)
VALUES (
    'Tropical Flowering Plants Workshop',
    'Explore flowering species that thrive in Bangladesh''s humid climate — Bougainvillea, Hibiscus, Ixora, and Water Lily. Learn pruning, propagation, and seasonal care tips.',
    '2026-09-13',
    'Paid',
    1, 200, 599.00
);
INSERT INTO workshops_branches (WorkshopID, BranchID)
SELECT LAST_INSERT_ID(), ID FROM branches WHERE Name = 'Agrabad Branch';

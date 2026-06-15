-- ══════════════════════════════════════════════════════════════
-- Bangladesh branch locations for EcoGrow
-- Run in phpMyAdmin AFTER uploading the updated files
-- ══════════════════════════════════════════════════════════════

-- Step 1: Remove workshop-branch links first (child rows), then branches (parent)
DELETE FROM workshops_branches;
DELETE FROM branches;

-- Step 2: Insert 6 real Bangladesh locations
INSERT INTO branches (Name, Location, Manager, Ratings, Details) VALUES
(
  'Gulshan Green House',
  'Plot 5, Road 103, Gulshan-2, Dhaka 1212',
  'Kamal Uddin',
  4.8,
  'Our flagship branch in the heart of Gulshan. Specialises in premium indoor plants, rare succulents, and luxury planters. Open Saturday–Thursday 9 AM–8 PM.'
),
(
  'Dhanmondi Garden Centre',
  'House 32, Road 4/A, Dhanmondi R/A, Dhaka 1209',
  'Nasrin Akter',
  4.6,
  'A favourite among plant lovers in Dhanmondi. Full range of outdoor plants, organic soil, and accessories. Home delivery available across Dhaka. Open daily 10 AM–9 PM.'
),
(
  'Uttara Plant Studio',
  'House 11, Road 7, Sector 4, Uttara Model Town, Dhaka 1230',
  'Farhan Ahmed',
  4.5,
  'Serving the northern part of Dhaka. Great selection of seasonal plants, flowering species, and gardening tools. Weekend workshops held here regularly.'
),
(
  'Mirpur EcoGrow Hub',
  'Block B, Section 11, Pallabi, Mirpur, Dhaka 1216',
  'Sadia Islam',
  4.3,
  'Affordable plants and accessories for Mirpur and surrounding areas. Bulk orders welcome for offices and housing societies. Open daily 9 AM–8 PM.'
),
(
  'Agrabad Branch',
  '45 Agrabad Commercial Area, Chattogram 4100',
  'Rafiqul Hossain',
  4.7,
  'EcoGrow''s first branch outside Dhaka. Popular for tropical plants suited to Chattogram''s climate. Also hosts monthly gardening events for the port city community.'
),
(
  'Sylhet Green House',
  'Zinda Bazar, Sylhet 3100',
  'Tahsin Rahman',
  4.4,
  'Located in the tea-garden capital of Bangladesh. Specialises in tea plants, herbs, and hillside species. A must-visit for nature lovers in Sylhet.'
);

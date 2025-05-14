-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 04:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecogrow`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `ID` int(11) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Position` varchar(20) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`ID`, `Name`, `Position`, `Phone`, `Email`, `Photo`, `Password`) VALUES
(1, 'Tom', 'Senior Manager', '01344586778', 'tom@gmail.com', 'https://pics.craiyon.com/2024-11-17/JrF05Z3zSVSYhqp3Ja3Hsw.webp', '1111'),
(2, 'Jerry', 'Junior Manager', '01344586773', 'jerry@gmail.com', 'https://images.hobbydb.com/processed_uploads/subject_photo/subject_photo/image/37013/1518467743-27497-2552/Jerry_20Mouse_large.jpg', '0000');

-- --------------------------------------------------------

--
-- Table structure for table `admin_confirms_orders`
--

CREATE TABLE `admin_confirms_orders` (
  `AdminID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `IsPending` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_confirms_orders`
--

INSERT INTO `admin_confirms_orders` (`AdminID`, `OrderID`, `Location`, `IsPending`) VALUES
(1, 4, NULL, 0),
(1, 5, NULL, 0),
(1, 6, NULL, 0),
(1, 7, NULL, 0),
(1, 8, '', 0),
(1, 9, '', 0),
(1, 10, '', 0),
(1, 11, '', 0),
(1, 12, '', 0),
(1, 13, '', 0),
(1, 14, '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_managers`
--

CREATE TABLE `admin_managers` (
  `ManagerID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_manages_suppliers`
--

CREATE TABLE `admin_manages_suppliers` (
  `AdminID` int(11) NOT NULL,
  `SupplierID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Ratings` decimal(3,2) DEFAULT NULL,
  `Manager` varchar(100) DEFAULT NULL,
  `Details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`ID`, `Name`, `Location`, `Ratings`, `Manager`, `Details`) VALUES
(1, 'Banani', 'KFS road, Banani', 4.50, 'Tom', 'HeadBranch of our Ecogrow'),
(2, 'Dhanmondi', 'Shimanto Shomvar', 5.00, 'Jerry', 'This is our most posh Branch. Here you will get all foreign plants.');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CustomerID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `AddedOn` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CustomerID`, `ProductID`, `AddedOn`) VALUES
(2, 18, '2025-05-14 07:54:51'),
(3, 3, '2025-05-11 09:16:19'),
(3, 7, '2025-05-11 10:22:39'),
(3, 22, '2025-05-11 09:18:43');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `ID` int(11) NOT NULL,
  `Name` varchar(40) NOT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Points` int(11) DEFAULT 0,
  `Type` enum('Guest','Registered') NOT NULL,
  `Coupon` varchar(50) DEFAULT NULL,
  `RegisteredPoints` int(11) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`ID`, `Name`, `Phone`, `Email`, `Points`, `Type`, `Coupon`, `RegisteredPoints`, `Password`) VALUES
(1, 'aditi roy adri', '01315841488', 'aditi.roy.adri@g.bracu.ac.bd', 0, 'Registered', NULL, NULL, '9090'),
(2, 'Shiva Prasad Sarkar ', '01712458856', 'shiva.prasad.sarkar@g.bracu.ac.bd', 0, 'Registered', NULL, NULL, 'abcd'),
(3, 'Ayon', '01315841499', 'aa@gmail.com', 0, 'Registered', NULL, NULL, 'aa'),
(4, 'aditya', '01712458856', 'adrirvai@gmail.com', 0, 'Registered', NULL, NULL, '1234'),
(5, 'Sutopa', '01315841488', 's@gmail.com', 0, 'Registered', NULL, NULL, '11');

-- --------------------------------------------------------

--
-- Table structure for table `customers_reviews`
--

CREATE TABLE `customers_reviews` (
  `CustomerID` int(11) NOT NULL,
  `Comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers_reviews`
--

INSERT INTO `customers_reviews` (`CustomerID`, `Comments`) VALUES
(2, 'one of the best online nursery');

-- --------------------------------------------------------

--
-- Table structure for table `customers_workshops`
--

CREATE TABLE `customers_workshops` (
  `CustomerID` int(11) NOT NULL,
  `WorkshopID` int(11) NOT NULL,
  `AccessPass` varchar(50) DEFAULT NULL,
  `Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers_workshops`
--

INSERT INTO `customers_workshops` (`CustomerID`, `WorkshopID`, `AccessPass`, `Date`) VALUES
(1, 1, '123', '2025-05-12'),
(1, 2, '123', '2025-05-12'),
(1, 3, '123', '2025-05-12'),
(2, 1, 'Registered', '2025-05-12'),
(2, 2, 'Registered', '2025-05-12'),
(3, 1, '123', '2025-05-12'),
(3, 2, '123', '2025-05-12');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `ID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `Product_Id` int(10) NOT NULL,
  `Date` date DEFAULT NULL,
  `Bill` decimal(10,2) DEFAULT NULL,
  `Count` int(11) DEFAULT NULL,
  `Address` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`ID`, `CustomerID`, `Product_Id`, `Date`, `Bill`, `Count`, `Address`) VALUES
(4, 3, 0, '2025-05-11', 3000.00, 3, 'lalhara'),
(5, 3, 0, '2025-05-11', 3602.00, 4, 'home'),
(6, 3, 0, '2025-05-11', 3000.00, 3, 'asss'),
(7, 2, 0, '2025-05-11', 1000.00, 1, 'aaaa'),
(8, 2, 0, '2025-05-11', 200.00, 1, 'a'),
(9, 3, 0, '2025-05-11', 300.00, 1, 'tangail'),
(10, 4, 0, '2025-05-11', 3000.00, 3, 'kalirbajar'),
(11, 1, 0, '2025-05-12', 1000.00, 1, 'amgo bari'),
(12, 1, 0, '2025-05-12', 200.00, 2, 'nanir bari'),
(13, 2, 0, '2025-05-12', 400.00, 2, 'adelide'),
(14, 2, 0, '2025-05-14', 400.00, 2, 'brac uni'),
(15, 2, 0, '2025-05-14', 1950.00, 5, 'aaaa'),
(16, 2, 1, '2025-05-14', 3000.00, 3, 'sdndsj');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Category` enum('Accessories','Plants') NOT NULL,
  `SubType` varchar(20) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Stock` int(11) DEFAULT 0,
  `Ratings` decimal(3,2) DEFAULT NULL,
  `Details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ID`, `Name`, `Category`, `SubType`, `Price`, `Stock`, `Ratings`, `Details`) VALUES
(1, 'Aloevera', 'Plants', 'Indoor', 1000.00, 456, 4.90, 'Aloe vera is a succulent plant species of the genus Aloe, widely known for its gel, which is often used in skincare and for medicinal purposes. It\'s a thick, short-stemmed plant that stores water in its leaves and contains numerous bioactive compounds. '),
(2, 'Money Plant', 'Plants', 'Indoor', 200.00, 1000, 3.00, 'The Money Plant, also known as Pothos or Epipremnum aureum, is a popular houseplant often associated with good luck and prosperity in various cultures, particularly in Feng Shui.\r\nThe belief in the Money Plant\'s ability to bring wealth is largely symbolic and based on cultural traditions rather than empirical data.'),
(3, 'Snake Plant', 'Plants', 'Indoor', 900.50, 60, 5.00, 'The snake plant, also known as Mother-in-Law\'s Tongue or Sansevieria, is a popular houseplant renowned for its easy care and air-purifying qualities. Native to tropical West Africa, these plants are known for their long, sword-shaped leaves and ability to thrive in low-light conditions. They are also prized for their ability to remove toxins from the air. '),
(4, 'Onion Plant', 'Plants', 'Outdoor', 100.00, 500, 4.70, 'Onion plants, scientifically known as Allium cepa, are herbaceous biennials in the amaryllis family, grown primarily for their edible bulbs. They are a widely cultivated species and are known for their flavorful bulbs, which are used in various culinary applications. '),
(5, 'Garlic Plant', 'Plants', 'Outdoor', 250.00, 490, 4.80, 'Although garlic can grow in different types of soil, loamy soil with natural drainage is optimum for this crop. It grows at an altitude of 1200 to 2000 m above sea level. It is sensitive to acidic and alkaline soils, hence, a pH of 6-8 is suitable for optimal growth of garlic.'),
(6, 'Ginger Plant', 'Plants', 'Outdoor', 300.00, 48, 2.70, 'The ginger plant has a thick, branched rhizome (underground stem) with a brown outer layer and yellow centre that has a spicy, citrusy aroma. Every year, it grows pseudostems (false stems made of tightly wrapped leaf bases) from the rhizome which bear narrow leaves.'),
(7, 'Carrot', 'Plants', 'Outdoor', 200.00, 99, 4.39, 'Though their roots will be slightly smaller than carrots grown in full sun, and they\'ll take a few weeks longer to mature, it is possible to grow a decent crop of carrots with minimal sunlight.'),
(8, 'Cucumber', 'Plants', 'Outdoor', 900.50, 690, 3.50, 'Cucumbers are ready for harvest 50 to 70 days from planting, depending on the variety. Depending on their use, harvest on the basis of size. Cucumbers taste best when harvested in the immature stage (Figure 2). Cucumbers should not be allowed to reach the yellowish stage as they become bitter with size.'),
(9, 'Watermelon Plant', 'Plants', 'Outdoor', 455.00, 40, 2.00, 'Watermelons are grown from a sprawling, vine-like plant belonging to the Cucurbitaceae family. The plant produces round, fleshy fruits known as watermelons. Watermelons are highly cultivated globally, with over 1,000 varieties. '),
(10, 'Orange', 'Plants', 'Outdoor', 390.00, 439, 4.50, 'orange, any of several species of small trees or shrubs of the genus Citrus of the family Rutaceae and their nearly round fruits, which have leathery and oily rinds and edible, juicy inner flesh.'),
(11, 'Lemon', 'Plants', 'Outdoor', 900.50, 5000, 3.00, 'The lemon plant forms an evergreen spreading bush or small tree, 3–6 metres (10–20 feet) high if not pruned. Its young oval leaves have a decidedly reddish tint; later they turn green. In some varieties the young branches of the lemon are angular; some have sharp thorns at the axils of the leaves'),
(12, 'Rose (red)', 'Plants', 'Outdoor', 900.50, 600, 4.00, '\r\nTheir stems are usually prickly and their glossy, green leaves have toothed edges. Rose flowers vary in size and shape. They burst with colours ranging from pastel pink, peach, and cream, to vibrant yellow, orange, and red. Many roses are fragrant, and some produce berry-like fruits called hips.'),
(13, 'Pink Rose', 'Plants', 'Outdoor', 900.50, 20, 5.00, 'Pink roses are a popular flower choice, symbolizing gratitude, appreciation, and admiration. They come in various shades, from soft blush to vibrant magenta, each conveying a slightly different nuance. Lighter pinks often represent gentleness and joy, while darker pinks can express deeper appreciation. Pink roses are also a common choice for expressing friendship or celebrating anniversaries'),
(14, 'Sunflower', 'Plants', 'Outdoor', 200.00, 2, 3.00, 'The common sunflower has a green erect stem covered in coarse hairs, growing on average around 2m tall. The leaves are broad, with serrated edges, and are alternately arranged on the stem. The \'flower\' of the common sunflower is actually a pseudanthium, or flowerhead, made up of many small flowers.'),
(15, 'Marigold', 'Plants', 'Outdoor', 1000.00, 5000, 4.90, 'Marigolds are very easy to grow and grow fast, which makes them great for children or gardening newbies. Marigolds need full sun all day to provide blooms all season long. Three common types are French, African, and Signet. Marigolds naturally repel pests such as deer or rabbits since they find their odor offensive.'),
(16, 'Jasmine', 'Plants', 'Outdoor', 100.00, 60000, 2.00, 'Known all over the world for its fragrant scent, common jasmine has been cultivated for at least 2000 years. Originating somewhere in Asia, jasmine was most likely one of the first plants to be cultivated specifically for its perfume.'),
(17, 'Water Pots ', 'Accessories', 'wooden', 2500.00, 590, 4.90, 'A watering can (or watering pot or watering jug) is a portable container, usually with a handle and a funnel, used to water plants by hand.'),
(18, 'Bonsai', 'Plants', 'Indoor', 30000.00, 0, 5.00, 'Bonsai are trees and plants grown in containers in such a way so that they look their most beautiful – even prettier than those growing in the wild. Cultivating bonsai, therefore, is a very artistic hobby as well as a traditional Japanese art.'),
(19, 'Hand Fork', 'Accessories', 'soil', 3500.00, 40, 1.50, 'They are often used for digging up weeds, hand forks are sometimes referred to as \'weeding forks\' but they are suited to many jobs around the garden, such as preparing planting holes, transplanting, aerating and mixing additives into your soil and are indispensable for levelling around border edges and tidying up the ...'),
(20, 'Pots', 'Accessories', 'glass', 900.00, 10000, 4.34, 'A flowerpot, planter, planterette or plant pot, is a container in which flowers and other plants are cultivated and displayed. Historically, and still to a significant extent today, they are made from plain terracotta with no ceramic glaze, with a round shape, tapering inwards.'),
(21, 'Rake', 'Accessories', 'soil', 3900.00, 5, 2.00, 'What is the use of rake in planting?\r\nA rake is a type of gardening or landscaping tool with a handle that ends in a head. You can use a rake for scooping, scraping, gathering, or leveling materials, such as soil, mulch, or leaves. Some rakes have flat heads; others have sharp metal tines that can break up compacted soil or rocks.'),
(22, 'Plant Shelf', 'Accessories', 'wooden', 4000.00, 226, 5.00, 'A plant stand is a piece of furniture designed to elevate potted plants, typically for display or to improve their access to light and air. They can range from simple, single-tier stands to multi-tiered shelves, and can be made of various materials like wood, metal, or rattan. Plant stands enhance a room\'s decor by creating visual interest and providing vertical space for plants. \r\n'),
(23, 'Fertilizer', 'Accessories', 'soil', 1000.00, 50, 3.00, 'We apply fertilizer to promote healthy plant growth including budding, flowers, fruit production and, in some cases, seed or nut production. Plants use a tremendous amount of energy to flower and produce fruit, seeds and nuts while continuing to develop a healthy root system and grow leaves for photosynthesis.');

-- --------------------------------------------------------

--
-- Table structure for table `products_branches`
--

CREATE TABLE `products_branches` (
  `ProductID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Stock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_photos`
--

CREATE TABLE `product_photos` (
  `ProductID` int(11) NOT NULL,
  `Photo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_photos`
--

INSERT INTO `product_photos` (`ProductID`, `Photo`) VALUES
(1, 'https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcRsrb-EX65ebih73UzPB_dgeUHURdKuzeU0_I4F3lw4Wv1OWyNviXdrOAlGfmdJmoHNZy1NdIXz4aLcrgFVq1jbBw'),
(2, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSD5lwsNNjPBWJpzI85UPTDQUaVAw-kUKltSw&s'),
(3, 'https://www.thespruce.com/thmb/CpEP-cPmDmz6kwdmVKbhcrdCJuY=/750x0/filters:no_upscale():max_bytes(150000):strip_icc():format(webp)/snake-plant-care-overview-1902772-04-d3990a1d0e1d4202a824e929abb12fc1-349b52d646f04f31962707a703b94298.jpeg'),
(4, 'https://i0.wp.com/upload.wikimedia.org/wikipedia/commons/thumb/9/93/ARS_red_onion.jpg/300px-ARS_red_onion.jpg'),
(5, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR1SH54dvkGgzrNW340m8n_HkDg02Jup5F1LmMwQzAc5XUC-MqJnlLxBvMzoAPav25CZwdF4nzkiBpm-uIWjRForSaqukRwM6zbs1ZO8aA'),
(6, 'https://cdn.britannica.com/19/231119-050-35483892/Indian-ginger-Zingiber-officinale.jpg'),
(7, 'https://assets-news.housing.com/news/wp-content/uploads/2023/07/07014344/How-to-grow-Carrot-plant-in-your-home-garden-02.jpg'),
(8, 'https://www.bhg.com/thmb/VJ8SG1wwokbpNRXspn-ZxSEKtZw=/1400x0/filters:no_upscale():strip_icc()/green-fingers-cucumber-0d9e0b36-10a18e309f664e028d33cccc3cc758de.jpg'),
(9, 'https://bonnieplants.com/cdn/shop/articles/BONNIE_watermelon_iStock-181067852-1800px_28032150-26a6-4cda-be5b-c4408112e3a6.jpg?v=1642541981'),
(10, 'https://cdn.britannica.com/67/174567-050-6C41DBC7/Grove-orange-trees-region-Costa-Blanca-Spain.jpg'),
(11, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQBQGWoFh7k0b_8Fob1_J6HEL_0_nK0TvdOfrdyeFiISmPovnHPSTdY0dbZaR8xLFG9A9NW3L6WI1SVwSK-OVTCNygQzH2PJJMMokXrng'),
(12, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQET-gpzURPOSh8nMCHnyBPyCiOmUv12R7iuQ&s'),
(13, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSrKYmiyyUjBgkTu2fCov1kwBNPyhfbEms6CQ&s'),
(14, 'https://hips.hearstapps.com/hmg-prod/images/sunforest-mix-sunflower-types-1586794598.jpg'),
(15, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTbvTNZn8XGeGFp-_e7JKyGPrghLwpJNq4VTcNpE9DvSDe5VulXfZvoV5Y3DOUC2zBmbkWyeVrG58R7szhzZxbZsQ'),
(16, 'https://encrypted-tbn2.gstatic.com/images?q=tbn:ANd9GcQE5OdoLxnmmRo8cHsPGUwnEZGNwBrt3tmgxYCo9oDNH0ROLk-p2C8D8NaLHXnlLVGJ6Mn09NSWRgx42f0DXMBGLDwjGK2C3fVO8N3CxYk'),
(17, 'https://www.thespruce.com/thmb/pSPyoupmG9lTwflF0h0zRo4hocM=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/GettyImages-1363118509-4f6c137447d2431c927d06b96ef1066b.jpg'),
(18, 'https://thumbs.dreamstime.com/b/small-tree-2933825.jpg'),
(19, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_7Y3CUs_CLuTsL-MS9IsUj6DjawQaQKRoaQ&s'),
(20, 'https://www.thesill.com/cdn/shop/articles/blog_the-sill_grow-pot-v2.jpg?v=1597704122&width=1100'),
(21, 'https://www.trees.com/wp-content/uploads/files/inline-images/types-of-rakes/Garden-Rake.jpg'),
(22, 'https://i5.walmartimages.com/seo/Bamworld-Tall-Plant-Stand-Outdoor-Black-Plant-Shelf-Indoor-Corner-Plant-Shelf-Flower-Stands-for-Living-Room-Balcony-and-Garden-9-pots_3d2ac483-6c4d-4a9e-b8f0-7259837b880e.1f994da151ab119b2ce24e714cfdfe0b.jpeg'),
(23, 'https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcRoNRz8rP2mEq3TLTY9O9jJrw0VasHiUHeCclba1VNC5rF9fy7MnRDfUzH_PN6VkBBckwqDxB_n4rK4FzsGjJ2HkwLJVkpCagqVCjylucA');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Mail` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Type` enum('Native','Foreign') NOT NULL,
  `ShipmentTime` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers_products`
--

CREATE TABLE `suppliers_products` (
  `SupplierID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workshops`
--

CREATE TABLE `workshops` (
  `WID` int(11) NOT NULL,
  `Topic` varchar(100) NOT NULL,
  `Subject` varchar(100) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Type` enum('Free','Paid') NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `Points` int(11) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workshops`
--

INSERT INTO `workshops` (`WID`, `Topic`, `Subject`, `Date`, `Type`, `CreatedBy`, `Points`, `Price`) VALUES
(1, 'Deforestation', 'Planting Tress', '2025-05-22', 'Paid', 1, 10, 500.00),
(2, 'Tree Planting', 'Care of trees', '2025-05-26', 'Free', 1, NULL, NULL),
(3, 'Medicine Tree', 'Qualities of Product', '2025-06-18', 'Free', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `workshops_branches`
--

CREATE TABLE `workshops_branches` (
  `WorkshopID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workshops_branches`
--

INSERT INTO `workshops_branches` (`WorkshopID`, `BranchID`) VALUES
(1, 1),
(2, 1),
(3, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `admin_confirms_orders`
--
ALTER TABLE `admin_confirms_orders`
  ADD PRIMARY KEY (`AdminID`,`OrderID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Indexes for table `admin_managers`
--
ALTER TABLE `admin_managers`
  ADD PRIMARY KEY (`ManagerID`,`EmployeeID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `admin_manages_suppliers`
--
ALTER TABLE `admin_manages_suppliers`
  ADD PRIMARY KEY (`AdminID`,`SupplierID`),
  ADD KEY `SupplierID` (`SupplierID`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CustomerID`,`ProductID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `customers_reviews`
--
ALTER TABLE `customers_reviews`
  ADD PRIMARY KEY (`CustomerID`);

--
-- Indexes for table `customers_workshops`
--
ALTER TABLE `customers_workshops`
  ADD PRIMARY KEY (`CustomerID`,`WorkshopID`),
  ADD KEY `WorkshopID` (`WorkshopID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `CustomerID` (`CustomerID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `products_branches`
--
ALTER TABLE `products_branches`
  ADD PRIMARY KEY (`ProductID`,`BranchID`),
  ADD KEY `BranchID` (`BranchID`);

--
-- Indexes for table `product_photos`
--
ALTER TABLE `product_photos`
  ADD PRIMARY KEY (`ProductID`,`Photo`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `suppliers_products`
--
ALTER TABLE `suppliers_products`
  ADD PRIMARY KEY (`SupplierID`,`ProductID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `workshops`
--
ALTER TABLE `workshops`
  ADD PRIMARY KEY (`WID`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `workshops_branches`
--
ALTER TABLE `workshops_branches`
  ADD PRIMARY KEY (`WorkshopID`,`BranchID`),
  ADD KEY `BranchID` (`BranchID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workshops`
--
ALTER TABLE `workshops`
  MODIFY `WID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_confirms_orders`
--
ALTER TABLE `admin_confirms_orders`
  ADD CONSTRAINT `admin_confirms_orders_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admins` (`ID`),
  ADD CONSTRAINT `admin_confirms_orders_ibfk_2` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`ID`);

--
-- Constraints for table `admin_managers`
--
ALTER TABLE `admin_managers`
  ADD CONSTRAINT `admin_managers_ibfk_1` FOREIGN KEY (`ManagerID`) REFERENCES `admins` (`ID`),
  ADD CONSTRAINT `admin_managers_ibfk_2` FOREIGN KEY (`EmployeeID`) REFERENCES `admins` (`ID`);

--
-- Constraints for table `admin_manages_suppliers`
--
ALTER TABLE `admin_manages_suppliers`
  ADD CONSTRAINT `admin_manages_suppliers_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admins` (`ID`),
  ADD CONSTRAINT `admin_manages_suppliers_ibfk_2` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`ID`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`ID`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`);

--
-- Constraints for table `customers_reviews`
--
ALTER TABLE `customers_reviews`
  ADD CONSTRAINT `customers_reviews_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`ID`);

--
-- Constraints for table `customers_workshops`
--
ALTER TABLE `customers_workshops`
  ADD CONSTRAINT `customers_workshops_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`ID`),
  ADD CONSTRAINT `customers_workshops_ibfk_2` FOREIGN KEY (`WorkshopID`) REFERENCES `workshops` (`WID`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`ID`);

--
-- Constraints for table `products_branches`
--
ALTER TABLE `products_branches`
  ADD CONSTRAINT `products_branches_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`),
  ADD CONSTRAINT `products_branches_ibfk_2` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`ID`);

--
-- Constraints for table `product_photos`
--
ALTER TABLE `product_photos`
  ADD CONSTRAINT `product_photos_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`);

--
-- Constraints for table `suppliers_products`
--
ALTER TABLE `suppliers_products`
  ADD CONSTRAINT `suppliers_products_ibfk_1` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`ID`),
  ADD CONSTRAINT `suppliers_products_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`);

--
-- Constraints for table `workshops`
--
ALTER TABLE `workshops`
  ADD CONSTRAINT `workshops_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `admins` (`ID`);

--
-- Constraints for table `workshops_branches`
--
ALTER TABLE `workshops_branches`
  ADD CONSTRAINT `workshops_branches_ibfk_1` FOREIGN KEY (`WorkshopID`) REFERENCES `workshops` (`WID`),
  ADD CONSTRAINT `workshops_branches_ibfk_2` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

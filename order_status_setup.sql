-- Run this in phpMyAdmin BEFORE uploading the updated PHP files
-- Creates the order_status table and backfills existing orders

CREATE TABLE IF NOT EXISTS `order_status` (
  `OrderID`   int(11)      NOT NULL,
  `Status`    varchar(20)  NOT NULL DEFAULT 'Placed',
  `Note`      varchar(255)          DEFAULT NULL,
  `UpdatedBy` int(11)               DEFAULT NULL,
  `UpdatedAt` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`OrderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill: give all existing orders a "Placed" status
INSERT IGNORE INTO order_status (OrderID, Status)
SELECT ID, 'Placed' FROM orders;

-- For already-confirmed orders, mark as Processing
INSERT INTO order_status (OrderID, Status)
SELECT aco.OrderID, 'Processing'
FROM admin_confirms_orders aco
WHERE aco.IsPending = 0
ON DUPLICATE KEY UPDATE Status = 'Processing';

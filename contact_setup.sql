-- ══════════════════════════════════════════════════════════════
-- Run this in phpMyAdmin to enable the Contact Us form
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`        int(11)      NOT NULL AUTO_INCREMENT,
  `Name`      varchar(100) NOT NULL,
  `Email`     varchar(150) NOT NULL,
  `Subject`   varchar(200) NOT NULL,
  `Message`   text         NOT NULL,
  `is_read`   tinyint(1)   NOT NULL DEFAULT 0,
  `CreatedAt` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

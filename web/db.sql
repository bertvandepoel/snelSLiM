SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `accounts` (
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `poweruser` tinyint(1) NOT NULL,
  `admin` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `corpora` (
  `id` int(11) NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `format` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `owner` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `c1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `c2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `freqnum` int(11) NOT NULL,
  `cutoff` decimal(8,6) NOT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `share_link` (
  `sharetoken` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reportid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `share_user` (
  `reportid` int(11) NOT NULL,
  `account` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `accounts`
  ADD PRIMARY KEY (`email`);

ALTER TABLE `corpora`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `corpora`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `share_link`
  ADD PRIMARY KEY (`sharetoken`),
  ADD UNIQUE KEY `reportid` (`reportid`);

ALTER TABLE `share_user`
  ADD PRIMARY KEY (`reportid`,`account`);

INSERT INTO `accounts` VALUES
('test@example.com', '$2y$10$d53lQJTJAn6EMuXsYf/NNeXjhkXWh.KorXcHCvBuYzkyQT1Pn84He', 1, 1);

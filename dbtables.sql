-- phpMyAdmin SQL Dump

-- Database: `YOURDBNAME`
--
-- Table structure for table `tci_Character`
--

CREATE TABLE IF NOT EXISTS `tci_Character` (
  `characterID` int(10) unsigned NOT NULL,
  `userID` int(10) unsigned NOT NULL,
  `characterName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `accessToken` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cacheTimer` int(10) unsigned DEFAULT NULL,
  `refreshToken` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contactCacheTimer` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`characterID`),
  KEY `fk_character_userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tci_Contact`
--

CREATE TABLE IF NOT EXISTS `tci_Contact` (
  `contactID` int(10) unsigned NOT NULL,
  `characterID` int(10) unsigned NOT NULL,
  `contactName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contactType` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `standing` float DEFAULT NULL,
  `watched` tinyint(3) unsigned DEFAULT NULL,
  `href` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`contactID`,`characterID`),
  KEY `fk_contact_characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tci_User`
--

CREATE TABLE IF NOT EXISTS `tci_User` (
  `userID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `primaryCharID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tci_Character`
--
ALTER TABLE `tci_Character`
  ADD CONSTRAINT `fk_character_userID` FOREIGN KEY (`userID`) REFERENCES `tci_User` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `tci_Contact`
--
ALTER TABLE `tci_Contact`
  ADD CONSTRAINT `fk_contact_characterID` FOREIGN KEY (`characterID`) REFERENCES `tci_Character` (`characterID`) ON DELETE CASCADE;

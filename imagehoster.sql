SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for imagehoster_data
-- ----------------------------
DROP TABLE IF EXISTS `imagehoster_data`;
CREATE TABLE `imagehoster_data` (
  `ID` varchar(20) NOT NULL,
  `ClientID` varchar(20) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Link` varchar(255) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Size` double DEFAULT NULL,
  `Width` double DEFAULT NULL,
  `Height` double DEFAULT NULL,
  `Data` text NOT NULL,
  `Created_at` datetime NOT NULL,
  `Created_by` varchar(50) NOT NULL,
  `Updated_at` datetime DEFAULT NULL,
  `Updated_by` varchar(50) DEFAULT NULL,
  `Updated_sys` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`,`ClientID`),
  KEY `Title` (`Title`) USING BTREE,
  KEY `Created_by` (`Created_by`),
  KEY `Created_at` (`Created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET FOREIGN_KEY_CHECKS=1;
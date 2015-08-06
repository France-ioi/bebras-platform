CREATE TABLE IF NOT EXISTS `algorea_registration` (
`ID` bigint(20) NOT NULL,
  `code` varchar(10) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `mailValidationHash` varchar(50) NOT NULL,
  `mailValidated` tinyint(1) NOT NULL,
  `email` varchar(50) NOT NULL,
  `algoreaAccount` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `algorea_registration`
 ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `code` (`code`);
ALTER TABLE `algorea_registration` ADD UNIQUE KEY `mailValidationHash` (`mailValidationHash`);
ALTER TABLE `algorea_registration`
MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contestant` ADD UNIQUE KEY `algoreaCode` (`algoreaCode`);

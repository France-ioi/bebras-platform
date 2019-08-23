CREATE TABLE `algorea_registration_original` (
  `ID` bigint NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `genre` tinyint NOT NULL,
  `email` varchar(70) NOT NULL,
  `zipCode` varchar(10) NOT NULL,
  `grade` int NOT NULL,
  `studentID` varchar(30) NOT NULL
) COLLATE 'utf8_unicode_ci';

ALTER TABLE `algorea_registration_original`
ADD PRIMARY KEY `ID` (`ID`);

ALTER TABLE `algorea_registration`
ADD `confirmed` tinyint NOT NULL DEFAULT '1';
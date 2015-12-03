CREATE TABLE IF NOT EXISTS `grade` (
  `ID` bigint(20) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `grade`
 ADD PRIMARY KEY (`ID`);

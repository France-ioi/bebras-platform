ALTER TABLE `user` ADD `awardPrintingDate` DATE NULL DEFAULT NULL AFTER `lastLoginDate`;

INSERT INTO `translations` (`ID`, `languageID`, `category`, `key`, `translation`, `iVersion`) VALUES (NULL, '1', 'admin_js', 'user_awardPrintingDate_label', 'Impression Algorea', ''), (NULL, '2', 'admin_js', 'user_awardPrintingDate_label', 'Algorea printing', '');


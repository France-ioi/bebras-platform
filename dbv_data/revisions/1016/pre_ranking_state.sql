ALTER TABLE `contest` CHANGE `status` `status` ENUM('FutureContest','RunningContest','PastContest','Other','Hidden','Closed','PreRanking') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Hidden';

INSERT INTO `translations` (`ID`, `languageID`, `category`, `key`, `translation`, `iVersion`) VALUES (NULL, '1', 'admin_js', 'option_preranking_contest', 'Pré-Classement', ''), (NULL, '2', 'admin_js', 'option_preranking_contest', 'Pre-Ranking', '');

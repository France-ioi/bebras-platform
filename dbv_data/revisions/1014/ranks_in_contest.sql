-- Award 1 is the best award, so minAward1Rank <= minAward2Rank

ALTER TABLE `contest` ADD `minAward1Rank` INT NULL DEFAULT NULL AFTER `folder`, ADD `minAward2Rank` INT NULL DEFAULT NULL AFTER `minAward1Rank`;
ALTER TABLE `history_contest` ADD `minAward1Rank` INT NULL DEFAULT NULL AFTER `folder`, ADD `minAward2Rank` INT NULL DEFAULT NULL AFTER `minAward1Rank`;

INSERT INTO `translations` (`ID`, `languageID`, `category`, `key`, `translation`, `iVersion`) VALUES (NULL, '1', 'admin', 'awards_title', 'Récompenses', ''), (NULL, '2', 'admin', 'awards_title', 'Awards', ''), (NULL, '2', 'admin', 'award1', 'List of contestants awarded with participation to FranceIOI contest', ''), (NULL, '1', 'admin', 'award1', 'Liste des participants ayant gagné une participation à FranceIOI', ''), (NULL, '1', 'admin', 'award2', 'Liste des participants ayant gagné une clé USB', ''), (NULL, '2', 'admin', 'award2', 'List of contestants awarded with USB key', '');

INSERT INTO `translations` (`ID`, `languageID`, `category`, `key`, `translation`, `iVersion`) VALUES (NULL, '1', 'admin_js', 'contest_minAward1Rank_label', 'Rang Rec. 1', ''), (NULL, '2', 'admin_js', 'contest_minAward1Rank_label', 'Rank 1st award', ''), (NULL, '2', 'admin_js', 'contest_minAward2Rank_label', 'Rank 2nd award', ''), (NULL, '1', 'admin_js', 'contest_minAward2Rank_label', 'Rang Rec. 2', '');

ALTER TABLE  `contest` ADD  `ranked`  ENUM( 'NotRanked',  'Ranked' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'NotRanked'  AFTER `status`;

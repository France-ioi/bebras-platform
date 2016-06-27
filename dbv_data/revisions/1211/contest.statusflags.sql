ALTER TABLE  `contest` CHANGE  `status`  `status` ENUM(  'Open',  'Closed' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'Open';
ALTER TABLE  `contest` ADD  `visibility`  ENUM( 'Visible',  'Hidden' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'Hidden';

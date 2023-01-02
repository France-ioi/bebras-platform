ALTER TABLE `contest` CHANGE `srlModule` `srlModule` ENUM('none','log','random','full','algorea') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'none';
ALTER TABLE `history_contest` CHANGE `srlModule` `srlModule` ENUM('none','log','random','full','algorea') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'none';

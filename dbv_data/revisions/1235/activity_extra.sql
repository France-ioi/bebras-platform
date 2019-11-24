ALTER TABLE `activity` CHANGE `type` `type` ENUM('load','attempt','submission','proxyload','extra') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

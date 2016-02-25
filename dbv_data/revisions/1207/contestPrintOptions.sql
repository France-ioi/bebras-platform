ALTER TABLE `contest` ADD `printCertificates` TINYINT(1) NOT NULL DEFAULT '0' AFTER `status`, ADD `certificateStringsName` VARCHAR(50) NULL DEFAULT NULL AFTER `printCertificates`;
ALTER TABLE `contest` ADD `showResults` TINYINT(1) NOT NULL DEFAULT '0' AFTER `certificateStringsName`;

ALTER TABLE `history_contest` ADD `printCertificates` TINYINT(1) NOT NULL DEFAULT '0' AFTER `status`, ADD `certificateStringsName` VARCHAR(50) NULL DEFAULT NULL AFTER `printCertificates`;
ALTER TABLE `history_contest` ADD `showResults` TINYINT(1) NOT NULL DEFAULT '0' AFTER `certificateStringsName`;
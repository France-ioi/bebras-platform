ALTER TABLE `contest` ADD `headerImageURL` TEXT NOT NULL AFTER `imageURL`, ADD `headerHTML` TEXT NOT NULL AFTER `headerImageURL`;
ALTER TABLE `history_contest` ADD `headerImageURL` TEXT NOT NULL AFTER `imageURL`, ADD `headerHTML` TEXT NOT NULL AFTER `headerImageURL`;

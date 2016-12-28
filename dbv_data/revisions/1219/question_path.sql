ALTER TABLE `question` ADD `path` TEXT NOT NULL AFTER `folder`;
ALTER TABLE `history_question` ADD `path` TEXT NOT NULL AFTER `folder`;
UPDATE question SET path = CONCAT(folder, '\/', `key`, '\/', 'index.html');

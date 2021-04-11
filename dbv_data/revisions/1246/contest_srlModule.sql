ALTER TABLE `contest`  ADD `srlModule` ENUM('none','log','full') NOT NULL DEFAULT 'none'  AFTER `logActivity`;
ALTER TABLE `history_contest`  ADD `srlModule` ENUM('none','log','full') NOT NULL DEFAULT 'none'  AFTER `logActivity`;

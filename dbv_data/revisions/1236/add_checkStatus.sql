ALTER TABLE `team_question`  ADD `checkStatus` ENUM('none','requested','error','done','difference','fixed','computed') NOT NULL DEFAULT 'none'  AFTER `scoreNeedsChecking`,  ADD   INDEX  (`checkStatus`);
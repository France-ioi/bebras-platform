ALTER TABLE `user` ADD `externalID` int unsigned NULL AFTER `ID`;
ALTER TABLE `user` ADD UNIQUE `externalID` (`externalID`);
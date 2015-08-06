ALTER TABLE `school_user` DROP INDEX `schoolID`, ADD UNIQUE `schoolID` ( `schoolID` , `userID` );

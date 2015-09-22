ALTER TABLE `history_contest_question` ADD `options` VARCHAR(200) NOT NULL DEFAULT '{}' AFTER `order`;
ALTER TABLE `history_user` ADD `awardPrintingDate` DATE NULL DEFAULT NULL AFTER `validated`;

ALTER TABLE `user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `school` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_school` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `school` CHANGE `userID` `userID` BIGINT(20) NOT NULL;
ALTER TABLE `history_school` CHANGE `userID` `userID` BIGINT(20) NOT NULL;

ALTER TABLE `school_user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_school_user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `school_user` CHANGE `userID` `userID` BIGINT(20) NOT NULL;
ALTER TABLE `history_school_user` CHANGE `userID` `userID` BIGINT(20) NOT NULL;

ALTER TABLE `school_user` CHANGE `schoolID` `schoolID` BIGINT(20) NOT NULL;
ALTER TABLE `history_school_user` CHANGE `schoolID` `schoolID` BIGINT(20) NOT NULL;

ALTER TABLE `school_year` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_school_year` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `school_year` CHANGE `schoolID` `userID` BIGINT(20) NOT NULL;
ALTER TABLE `history_school_year` CHANGE `schoolID` `userID` BIGINT(20) NOT NULL;

ALTER TABLE `team` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_team` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `team` CHANGE `groupID` `groupID` BIGINT(20) NOT NULL;
ALTER TABLE `history_team` CHANGE `groupID` `groupID` BIGINT(20) NOT NULL;

ALTER TABLE `team_connection` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_team_connection` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `team_connection` CHANGE `teamID` `teamID` BIGINT(20) NOT NULL;
ALTER TABLE `history_team_connection` CHANGE `teamID` `teamID` BIGINT(20) NOT NULL;

ALTER TABLE `team_question` CHANGE `teamID` `teamID` BIGINT(20) NOT NULL;
ALTER TABLE `team_question` CHANGE `questionID` `questionID` BIGINT(20) NOT NULL;

ALTER TABLE `tm_platforms` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `user_user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_user_user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `user_user` CHANGE `userID` `userID` BIGINT(20) NOT NULL;
ALTER TABLE `history_user_user` CHANGE `userID` `userID` BIGINT(20) NOT NULL;

ALTER TABLE `user_user` CHANGE `targetUserID` `targetUserID` BIGINT(20) NOT NULL;
ALTER TABLE `history_user_user` CHANGE `targetUserID` `targetUserID` BIGINT(20) NOT NULL;

ALTER TABLE `question` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_question` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `recommend_school_listedschool` CHANGE `schoolID` `schoolID` BIGINT(20) NOT NULL;
ALTER TABLE `recommend_school_listedschool` CHANGE `listedschoolID` `listedschoolID` BIGINT(20) NOT NULL;

ALTER TABLE `recommend_user` CHANGE `userID` `userID` BIGINT(20) NOT NULL;

ALTER TABLE `certi_queue` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `certi_queue` CHANGE `schoolID` `schoolID` BIGINT(20) NOT NULL;

ALTER TABLE `contest` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_contest` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `contestant` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_contestant` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `contestant` CHANGE `teamID` `teamID` BIGINT(20) NOT NULL;
ALTER TABLE `history_contestant` CHANGE `teamID` `teamID` BIGINT(20) NOT NULL;

ALTER TABLE `contestant` CHANGE `cached_schoolID` `cached_schoolID` BIGINT(20) NOT NULL;
ALTER TABLE `history_contestant` CHANGE `cached_schoolID` `cached_schoolID` BIGINT(20) NOT NULL;

ALTER TABLE `contest_question` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_contest_question` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `contest_question` CHANGE `contestID` `contestID` BIGINT(20) NOT NULL;
ALTER TABLE `history_contest_question` CHANGE `contestID` `contestID` BIGINT(20) NOT NULL;

ALTER TABLE `contest_question` CHANGE `questionID` `questionID` BIGINT(20) NOT NULL;
ALTER TABLE `history_contest_question` CHANGE `questionID` `questionID` BIGINT(20) NOT NULL;

ALTER TABLE `group` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_group` CHANGE `ID` `ID` BIGINT(20) NOT NULL;

ALTER TABLE `group` CHANGE `contestID` `contestID` BIGINT(20) NOT NULL;
ALTER TABLE `history_group` CHANGE `contestID` `contestID` BIGINT(20) NOT NULL;

ALTER TABLE `group` CHANGE `userID` `userID` BIGINT(20) NOT NULL;
ALTER TABLE `history_group` CHANGE `userID` `userID` BIGINT(20) NOT NULL;

ALTER TABLE `group` CHANGE `schoolID` `schoolID` BIGINT(20) NOT NULL;
ALTER TABLE `history_group` CHANGE `schoolID` `schoolID` BIGINT(20) NOT NULL;

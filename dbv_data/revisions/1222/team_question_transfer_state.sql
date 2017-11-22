CREATE TABLE IF NOT EXISTS `team_question_transfer_state` (
  `ID` bigint(20) NOT NULL,
  `startTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `team_question_transfer_state` (`ID`, `startTime`) VALUES ('0', '1970-01-01 00:00:00');

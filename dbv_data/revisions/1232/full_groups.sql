CREATE ALGORITHM=TEMPTABLE VIEW full_groups AS SELECT `group`.*, user_user.targetUserID FROM `group` LEFT JOIN user_user ON `group`.userID = user_user.userID AND user_user.accessType <> 'none';

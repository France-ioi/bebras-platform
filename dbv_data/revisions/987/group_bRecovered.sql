ALTER TABLE `group` ADD `bRecovered` BOOLEAN NOT NULL DEFAULT FALSE AFTER `isPublic`;

INSERT INTO `translations` (`ID`, `languageID`, `category`, `key`, `translation`, `iVersion`) VALUES
(NULL, '1', 'contest', 'group_session_expired_recover', '<p>Ce groupe a déjà servi il y a plus de 30 minutes et ne peut plus être utilisé.</p><p>S\'il a été utilisé par erreur, il est possible de regénérer un groupe équivalent.</p><p>Pour cela, l\'enseignant doit saisir le code de secours du groupe <b>sur un seul poste</b>, ci-dessous, <b>sans le donner aux élèves</b> :</p>', ''),
(NULL, '2', 'contest', 'group_session_expired_recover', '<p>This group\'s session started more than 30 minutes ago and cannot be used anymore.</p><p>If it has been used by mistake, it is possible to generate a new group.</p><p>To do so, the teacher must type the group\'s recovery code <b>on only one computer</b>, herebelow, <b>without giving it to the students</b>:</p>', ''),
(NULL, '2', 'contest', 'submitPass', 'Submit recovery code', ''),
(NULL, '1', 'contest', 'submitPass', 'Soumettre le code de secours', '');

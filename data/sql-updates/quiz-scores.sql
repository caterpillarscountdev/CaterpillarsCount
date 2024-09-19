CREATE TABLE `QuizScore` (
  `ID` int(11) NOT NULL,
  `UserFK` int(11) NOT NULL,
  `Score` smallint(6) NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

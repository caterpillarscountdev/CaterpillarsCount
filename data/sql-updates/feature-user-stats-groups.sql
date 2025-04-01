CREATE TABLE `UserGroup` (
  `ID` int(11) NOT NULL,
  `UserFKOfManager` int(11) NOT NULL,
  `Name` text NOT NULL,
  `Emails` text NOT NULL,
  `RequestedEmails` text NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `UserGroup`
  ADD PRIMARY KEY (`ID`);
ALTER TABLE `UserGroup`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

CREATE TABLE `UserGroupConsent` (
  `ID` int(11) NOT NULL,
  `UserGroupFK` int(11) NOT NULL,
  `UserFK` int(11) NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `UserGroupConsent`
  ADD PRIMARY KEY (`ID`);
ALTER TABLE `UserGroupConsent`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `UserGroupConsent`
  ADD UNIQUE INDEX user_group_unique (`UserGroupFK`, `UserFK`);

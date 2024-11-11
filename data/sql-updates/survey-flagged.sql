CREATE TABLE `SurveyFlagged` (
  `ID` int(11) NOT NULL,
  `SurveyFK` int(11) NOT NULL,
  `NumberOfLeavesLow` int(1),
  `NumberOfLeavesHigh` int(1),
  `NumberOfLeavesOverride` int(1),
  `AverageLeafLengthHigh` int(1),
  `AverageLeafLengthOverride` int(1),
  `ArthropodLengthHigh` int(1),
  `ArthropodLengthOverride` int(1),
  `ArthropodQuantityHigh` int(1),
  `ArthropodQuantityOverride` int(1), 
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `SurveyFlagged`
  ADD PRIMARY KEY (`ID`);
ALTER TABLE `SurveyFlagged`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `SurveyFlagged`
  ADD CONSTRAINT unique_survey UNIQUE INDEX (SurveyFK);

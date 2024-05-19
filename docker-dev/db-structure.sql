-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: 172.30.124.76
-- Generation Time: Apr 18, 2024 at 12:59 PM
-- Server version: 5.6.39
-- PHP Version: 7.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `CaterpillarsCount`
--

-- --------------------------------------------------------

--
-- Table structure for table `ArthropodQuizQuestions`
--

CREATE TABLE `ArthropodQuizQuestions` (
  `ID` int(11) NOT NULL,
  `PhotoURL` text NOT NULL,
  `Answer` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ArthropodSighting`
--

CREATE TABLE `ArthropodSighting` (
  `ID` int(11) NOT NULL,
  `SurveyFK` int(11) NOT NULL,
  `OriginalGroup` tinytext NOT NULL,
  `UpdatedGroup` tinytext NOT NULL,
  `Length` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `PhotoURL` text NOT NULL,
  `Notes` text NOT NULL,
  `Pupa` tinyint(1) NOT NULL DEFAULT '0',
  `Hairy` tinyint(1) NOT NULL,
  `Rolled` tinyint(1) NOT NULL,
  `Tented` tinyint(1) NOT NULL,
  `OriginalSawfly` tinyint(1) NOT NULL,
  `UpdatedSawfly` tinyint(1) NOT NULL,
  `OriginalBeetleLarva` tinyint(1) NOT NULL,
  `UpdatedBeetleLarva` tinyint(1) NOT NULL,
  `NeedToSendToINaturalist` tinyint(1) NOT NULL,
  `INaturalistID` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `CachedResult`
--

CREATE TABLE `CachedResult` (
  `ID` int(11) NOT NULL,
  `Name` text NOT NULL,
  `Timestamp` int(11) NOT NULL,
  `Result` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `CronJobStatus`
--

CREATE TABLE `CronJobStatus` (
  `ID` int(11) NOT NULL,
  `Name` text NOT NULL,
  `Processing` tinyint(1) NOT NULL,
  `Iteration` int(11) NOT NULL,
  `UTCLastCalled` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DisputedIdentification`
--

CREATE TABLE `DisputedIdentification` (
  `ID` int(11) NOT NULL,
  `ArthropodSightingFK` int(11) NOT NULL,
  `OriginalGroup` tinytext NOT NULL,
  `SuggestedGroups` text NOT NULL,
  `SupportingIdentifications` smallint(6) NOT NULL,
  `DisputingIdentifications` smallint(6) NOT NULL,
  `ExpertIdentification` text NOT NULL,
  `INaturalistObservationURL` tinytext NOT NULL,
  `LastUpdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Download`
--

CREATE TABLE `Download` (
  `ID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `UTCTime` time NOT NULL,
  `IP` tinytext NOT NULL,
  `Page` tinytext NOT NULL,
  `File` tinytext NOT NULL,
  `Filters` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ExpertIdentification`
--

CREATE TABLE `ExpertIdentification` (
  `ID` int(11) NOT NULL,
  `ArthropodSightingFK` int(11) NOT NULL,
  `OriginalGroup` tinytext NOT NULL,
  `Rank` text NOT NULL,
  `TaxonName` text NOT NULL,
  `StandardGroup` tinytext NOT NULL,
  `BeetleLarvaUpdated` tinyint(1) NOT NULL,
  `SawflyUpdated` tinyint(1) NOT NULL,
  `Agreement` smallint(6) NOT NULL,
  `RunnerUpAgreement` smallint(6) NOT NULL,
  `INaturalistObservationURL` tinytext NOT NULL,
  `LastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ManagerRequest`
--

CREATE TABLE `ManagerRequest` (
  `ID` int(11) NOT NULL,
  `UserFKOfManager` int(11) NOT NULL,
  `SiteFK` int(11) NOT NULL,
  `HasCompleteAuthority` tinyint(1) NOT NULL,
  `Status` tinytext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Plant`
--

CREATE TABLE `Plant` (
  `ID` int(11) NOT NULL,
  `SiteFK` int(11) NOT NULL,
  `Circle` int(11) NOT NULL,
  `Orientation` tinytext NOT NULL,
  `Code` tinytext NOT NULL,
  `Species` tinytext NOT NULL,
  `IsConifer` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Site`
--

CREATE TABLE `Site` (
  `ID` int(11) NOT NULL,
  `UserFKOfCreator` int(11) NOT NULL,
  `DateEstablished` date NOT NULL,
  `Name` text NOT NULL,
  `Description` tinytext NOT NULL,
  `URL` text NOT NULL,
  `Latitude` double NOT NULL,
  `Longitude` double NOT NULL,
  `Region` tinytext NOT NULL,
  `SaltedPasswordHash` text NOT NULL,
  `Salt` text NOT NULL,
  `OpenToPublic` tinyint(1) NOT NULL,
  `Active` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `SiteUserPreset`
--

CREATE TABLE `SiteUserPreset` (
  `ID` int(11) NOT NULL,
  `SiteFK` int(11) NOT NULL,
  `UserFK` int(11) NOT NULL,
  `ObservationMethod` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `SiteUserValidation`
--

CREATE TABLE `SiteUserValidation` (
  `ID` int(11) NOT NULL,
  `UserFK` int(11) NOT NULL,
  `SiteFK` int(11) NOT NULL,
  `SaltedSitePasswordHash` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Survey`
--

CREATE TABLE `Survey` (
  `ID` int(11) NOT NULL,
  `SubmissionTimestamp` int(11) NOT NULL DEFAULT '0',
  `UserFKOfObserver` int(11) NOT NULL,
  `PlantFK` int(11) NOT NULL,
  `LocalDate` date NOT NULL,
  `LocalTime` time NOT NULL,
  `ObservationMethod` tinytext NOT NULL,
  `Notes` text NOT NULL,
  `WetLeaves` tinyint(1) NOT NULL,
  `PlantSpecies` tinytext NOT NULL,
  `NumberOfLeaves` smallint(3) NOT NULL DEFAULT '-1',
  `AverageLeafLength` tinyint(2) NOT NULL DEFAULT '-1',
  `HerbivoryScore` tinyint(2) NOT NULL DEFAULT '-1',
  `AverageNeedleLength` tinyint(2) NOT NULL DEFAULT '-1',
  `LinearBranchLength` smallint(3) NOT NULL DEFAULT '-1',
  `SubmittedThroughApp` tinyint(1) NOT NULL,
  `ReviewedAndApproved` tinyint(1) NOT NULL DEFAULT '0',
  `MinimumTemperature` float NOT NULL DEFAULT '9999',
  `MaximumTemperature` float NOT NULL DEFAULT '9999',
  `NeedToSendToSciStarter` tinyint(1) NOT NULL,
  `CORRESPONDING_OLD_DATABASE_SURVEY_ID` int(11) NOT NULL DEFAULT '0',
  `QCComment` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `TemporaryEmailLog`
--

CREATE TABLE `TemporaryEmailLog` (
  `ID` int(11) NOT NULL,
  `UserIdentifier` text NOT NULL,
  `EmailTypeIdentifier` tinytext NOT NULL,
  `Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `TemporaryExpertIdentificationChangeLog`
--

CREATE TABLE `TemporaryExpertIdentificationChangeLog` (
  `ID` int(11) NOT NULL,
  `ArthropodSightingFK` int(11) NOT NULL,
  `PreviousExpertIdentification` text NOT NULL,
  `NewExpertIdentification` text NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `ID` int(11) NOT NULL,
  `FirstName` tinytext NOT NULL,
  `LastName` tinytext NOT NULL,
  `Hidden` tinyint(1) DEFAULT '0',
  `DesiredEmail` text NULL,
  `Email` text NOT NULL,
  `INaturalistObserverID` text NULL,
  `SaltedPasswordHash` text NOT NULL,
  `Salt` text NOT NULL,
  `EmailVerificationCode` tinytext NULL,
  `INaturalistSubmissions` int(11) NULL,
  `SupportedINaturalistSubmissions` int(11) NULL,
  `OverturnedINaturalistSubmissions` int(11) NULL,
  `INaturalistSubmissionsLastUpdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `VirtualSurveyScore`
--

CREATE TABLE `VirtualSurveyScore` (
  `ID` int(11) NOT NULL,
  `UserFK` int(11) NOT NULL,
  `Score` smallint(6) NOT NULL,
  `PercentFound` float NOT NULL,
  `IdentificationAccuracy` float NOT NULL,
  `LengthAccuracy` float NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ArthropodQuizQuestions`
--
ALTER TABLE `ArthropodQuizQuestions`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `ArthropodSighting`
--
ALTER TABLE `ArthropodSighting`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SurveyFKIndex` (`SurveyFK`),
  ADD KEY `GroupIndex` (`OriginalGroup`(255)),
  ADD KEY `Quantity` (`Quantity`);

--
-- Indexes for table `CachedResult`
--
ALTER TABLE `CachedResult`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `CronJobStatus`
--
ALTER TABLE `CronJobStatus`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `DisputedIdentification`
--
ALTER TABLE `DisputedIdentification`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `Download`
--
ALTER TABLE `Download`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `ExpertIdentification`
--
ALTER TABLE `ExpertIdentification`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ArthropodSightingFKIndex` (`ArthropodSightingFK`) USING BTREE;

--
-- Indexes for table `ManagerRequest`
--
ALTER TABLE `ManagerRequest`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `Plant`
--
ALTER TABLE `Plant`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SiteFKIndex` (`SiteFK`),
  ADD KEY `SpeciesIndex` (`Species`(255));

--
-- Indexes for table `Site`
--
ALTER TABLE `Site`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `SiteUserPreset`
--
ALTER TABLE `SiteUserPreset`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `SiteUserValidation`
--
ALTER TABLE `SiteUserValidation`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `Survey`
--
ALTER TABLE `Survey`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `LocalDateIndex` (`LocalDate`),
  ADD KEY `PlantFKIndex` (`PlantFK`),
  ADD KEY `UserFKOfObserverIndex` (`UserFKOfObserver`),
  ADD KEY `PlantSpeciesIndex` (`PlantSpecies`(255)),
  ADD KEY `LocalTimeIndex` (`LocalTime`);

--
-- Indexes for table `TemporaryEmailLog`
--
ALTER TABLE `TemporaryEmailLog`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `TemporaryExpertIdentificationChangeLog`
--
ALTER TABLE `TemporaryExpertIdentificationChangeLog`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `VirtualSurveyScore`
--
ALTER TABLE `VirtualSurveyScore`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ArthropodQuizQuestions`
--
ALTER TABLE `ArthropodQuizQuestions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=577;

--
-- AUTO_INCREMENT for table `ArthropodSighting`
--
ALTER TABLE `ArthropodSighting`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157221;

--
-- AUTO_INCREMENT for table `CachedResult`
--
ALTER TABLE `CachedResult`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3232;

--
-- AUTO_INCREMENT for table `CronJobStatus`
--
ALTER TABLE `CronJobStatus`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `DisputedIdentification`
--
ALTER TABLE `DisputedIdentification`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3310;

--
-- AUTO_INCREMENT for table `Download`
--
ALTER TABLE `Download`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10502;

--
-- AUTO_INCREMENT for table `ExpertIdentification`
--
ALTER TABLE `ExpertIdentification`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12867;

--
-- AUTO_INCREMENT for table `ManagerRequest`
--
ALTER TABLE `ManagerRequest`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;

--
-- AUTO_INCREMENT for table `Plant`
--
ALTER TABLE `Plant`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2147483648;

--
-- AUTO_INCREMENT for table `Site`
--
ALTER TABLE `Site`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=416;

--
-- AUTO_INCREMENT for table `SiteUserPreset`
--
ALTER TABLE `SiteUserPreset`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2250;

--
-- AUTO_INCREMENT for table `SiteUserValidation`
--
ALTER TABLE `SiteUserValidation`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2646;

--
-- AUTO_INCREMENT for table `Survey`
--
ALTER TABLE `Survey`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167451;

--
-- AUTO_INCREMENT for table `TemporaryEmailLog`
--
ALTER TABLE `TemporaryEmailLog`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `TemporaryExpertIdentificationChangeLog`
--
ALTER TABLE `TemporaryExpertIdentificationChangeLog`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11638;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4039;

--
-- AUTO_INCREMENT for table `VirtualSurveyScore`
--
ALTER TABLE `VirtualSurveyScore`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=357;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

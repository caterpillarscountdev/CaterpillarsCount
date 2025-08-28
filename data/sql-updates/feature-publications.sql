CREATE TABLE `Publication` (
  `ID` int(11) NOT NULL,
  `Citation` text NOT NULL,
  `DOI` text NOT NULL,
  `Link` text NOT NULL,
  `Image` text NOT NULL,
  `Order` int(11) NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `Publication`
  ADD PRIMARY KEY (`ID`);
ALTER TABLE `Publication`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

CREATE TABLE `PublicationSites` (
  `PublicationFK` int(11) NOT NULL,
  `SiteFK` int(11) NOT NULL,
  `NumberOfSurveys` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `PublicationSites`
  ADD PRIMARY KEY (`PublicationFK`, `SiteFK`);


CREATE TABLE `PublicationUsers` (
  `PublicationFK` int(11) NOT NULL,
  `UserFK` int(11) NOT NULL,
  `NumberOfSurveys` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `PublicationUsers`
  ADD PRIMARY KEY (`PublicationFK`, `UserFK`);

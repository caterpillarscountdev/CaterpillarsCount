ALTER TABLE `Survey`
  ADD COLUMN `ReviewedAndApprovedSite` tinyint(1) DEFAULT 0;

ALTER TABLE `Survey`
  ADD COLUMN `QCNumberOfLeavesHigh` tinyint(1) ;
ALTER TABLE `Survey`
  ADD COLUMN `QCNumberOfLeavesOK` tinyint(1) ;
ALTER TABLE `Survey`
  ADD COLUMN `QCAverageLeafLengthHigh` tinyint(1) ;
ALTER TABLE `Survey`
  ADD COLUMN `QCAverageLeafLengthOK` tinyint(1) ;
ALTER TABLE `Survey`
  ADD COLUMN `QCArthropodLengthHigh` tinyint(1) ;
ALTER TABLE `Survey`
  ADD COLUMN `QCArthropodLengthOK` tinyint(1) ;
ALTER TABLE `Survey`
  ADD COLUMN `QCArthropodQuantityHigh` tinyint(1) ;
ALTER TABLE `Survey`
  ADD COLUMN `QCArthropodQuantityOK` tinyint(1) ;

ALTER TABLE `ArthropodSighting`
  ADD COLUMN `QCArthropodLengthHigh` tinyint(1) ;
ALTER TABLE `ArthropodSighting`
  ADD COLUMN `QCArthropodLengthOK` tinyint(1) ;
ALTER TABLE `ArthropodSighting`
  ADD COLUMN `QCArthropodQuantityHigh` tinyint(1) ;
ALTER TABLE `ArthropodSighting`
  ADD COLUMN `QCArthropodQuantityOK` tinyint(1) ;

  

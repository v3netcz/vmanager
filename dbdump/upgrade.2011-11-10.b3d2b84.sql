ALTER TABLE `pm_tickets` CHANGE `state` `state` VARCHAR(64)  NOT NULL  DEFAULT '';
UPDATE `pm_tickets` SET `state` = 'new' WHERE `state` <> 1;
UPDATE `pm_tickets` SET `state` = 'solved' WHERE `state` = 1;

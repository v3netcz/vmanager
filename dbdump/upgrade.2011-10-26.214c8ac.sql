ALTER TABLE `pm_projects` ADD `description` TEXT NOT NULL  AFTER `name`;
ALTER TABLE `pm_projects` ADD `deadline` DATE  NULL  AFTER `description`;
ALTER TABLE `pm_projects` ADD `assignedTo` SMALLINT(6)  NULL  DEFAULT NULL  AFTER `deadline`;
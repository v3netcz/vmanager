-- Create syntax for TABLE 'github_commits'
CREATE TABLE `github_commits` (
  `id` char(40) NOT NULL DEFAULT '',
  `repository` tinyint(3) unsigned NOT NULL,
  `author` tinyint(3) unsigned NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL,
  `url` varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `repository` (`repository`)
);

-- Create syntax for TABLE 'github_repositories'
CREATE TABLE `github_repositories` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL DEFAULT '',
  `name` varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
);

-- Create syntax for TABLE 'github_users'
CREATE TABLE `github_users` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `email` varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
);
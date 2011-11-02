CREATE DATABASE `mossad` DEFAULT CHARACTER SET utf8 ;
USE `mossad`;

GRANT SELECT,INSERT,UPDATE,DELETE ON mossad.* TO 'mossad'@'localhost' IDENTIFIED BY 'SuperSecret99';

CREATE TABLE IF NOT EXISTS `User` (
  `userName` char(16) NOT NULL,
  `secret` varchar(32) NOT NULL,
  `pin` char(8),
  `hotpCounter` int(8) NOT NULL default '0',
  `invalidLogins` tinyint(1) not null default 0,
  PRIMARY KEY  (`userName`)
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `Guest` (
  `userName` char(16) NOT NULL,
  `password` varchar(32) NOT NULL,
  `dateCreation` timestamp default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`userName`),
  CONSTRAINT `fkUserNameGuest` FOREIGN KEY (`userName`) references `User` (`userName`) on delete cascade
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `Log` (
  `time` timestamp default CURRENT_TIMESTAMP,
  `userName` char(16) NOT NULL,
  `passPhrase` char(12),
  `message` varchar(256),
  CONSTRAINT `fkUserName` FOREIGN KEY (`userName`) references `User` (`userName`) on delete cascade
) ENGINE=InnoDB CHARSET=utf8;


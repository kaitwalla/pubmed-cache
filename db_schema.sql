CREATE TABLE `pubmed` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext NOT NULL,
  `slug` mediumtext NOT NULL,
  `pubmed_url` longtext NOT NULL,
  `data` longtext NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `SLUG` (`slug`(200))
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

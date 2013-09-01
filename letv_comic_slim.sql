-- Adminer 3.6.4 MySQL dump

SET NAMES utf8;

DROP DATABASE IF EXISTS `letv_comic_slim`;
CREATE DATABASE `letv_comic_slim` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */;
USE `letv_comic_slim`;

DROP TABLE IF EXISTS `comics`;
CREATE TABLE `comics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8 NOT NULL COMMENT '动漫名称',
  `original` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '原作',
  `supervision` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '监督',
  `region` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '国家/地区',
  `year` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '年代',
  `synopsis` text CHARACTER SET utf8 NOT NULL COMMENT '剧情介绍',
  `cover` varchar(256) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '封面',
  `episode_qty` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '剧集数量',
  `complete` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 更新中；1 已完结',
  `letv_id` int(10) unsigned NOT NULL,
  `letv_url` varchar(256) CHARACTER SET utf8 NOT NULL,
  `letv_cover_url` varchar(256) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `letv_id` (`letv_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2687 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `comics_seiyuus`;
CREATE TABLE `comics_seiyuus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comic_id` int(10) unsigned NOT NULL,
  `seiyuu_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `comic_id` (`comic_id`),
  KEY `seiyuu_id` (`seiyuu_id`),
  CONSTRAINT `comics_seiyuus_ibfk_2` FOREIGN KEY (`seiyuu_id`) REFERENCES `seiyuus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comics_seiyuus_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5178 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `comics_types`;
CREATE TABLE `comics_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comic_id` int(10) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `comic_id` (`comic_id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `comics_types_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comics_types_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6749 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `episodes`;
CREATE TABLE `episodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comic_id` int(10) unsigned NOT NULL,
  `series_id` int(10) unsigned NOT NULL,
  `name` varchar(32) CHARACTER SET utf8 NOT NULL,
  `duration` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '视频时长(s)',
  `cover` varchar(256) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '封面',
  `letv_url` varchar(256) CHARACTER SET utf8 NOT NULL,
  `letv_cover_url` varchar(256) CHARACTER SET utf8 NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comic_id_series_id` (`comic_id`,`series_id`),
  CONSTRAINT `episodes_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69492 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `proxies`;
CREATE TABLE `proxies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(16) CHARACTER SET utf8 NOT NULL,
  `port` int(10) unsigned NOT NULL,
  `type` varchar(8) CHARACTER SET utf8 NOT NULL,
  `region` varchar(32) CHARACTER SET utf8 NOT NULL,
  `available` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 不可用；1 可用',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `seiyuus`;
CREATE TABLE `seiyuus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=813 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `types`;
CREATE TABLE `types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- 2013-09-01 23:03:30

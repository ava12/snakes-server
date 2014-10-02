-- phpMyAdmin SQL Dump
-- version 3.5.6
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Июн 07 2014 г., 23:43
-- Версия сервера: 5.6.10-log
-- Версия PHP: 5.3.10

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- База данных: `snakes`
--

DELIMITER $$
--
-- Процедуры
--
DROP PROCEDURE IF EXISTS `delete_zero_refs`$$
CREATE PROCEDURE `delete_zero_refs`()
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
begin
 delete from `snake` where `refs` <= 0;
 delete from `fight` where `refs` <= 0;
 delete from `session` where `expires` >= NOW();
end$$

DROP PROCEDURE IF EXISTS `update_fight_list`$$
CREATE PROCEDURE `update_fight_list`(IN `@player_id` INT, IN `@list_type` ENUM('ordered','challenged'), IN `@fight_id` INT)
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
begin
 insert into `fightlist` (`player_id`, `type`, `fight_id`)
 values (`@player_id`, `@list_type`, `@fight_id`);
 delete from `fightlist`
 where `player_id` = `@player_id` and `type` = `@list_type` and `fight_id` in (
  select * from (
   select `fight_id` from `fightlist`
   inner join `fight` on `fightlist`.`fight_id` = `fight`.`id`
   where `player_id` = `@player_id` and `fightlist`.`type` = `@list_type`
   order by `fight`.`time` desc limit 1000 offset 10
	) as t
 );
end$$

DROP FUNCTION IF EXISTS `can_view_fight`$$
CREATE FUNCTION `can_view_fight`(`@player_id` INT, `@fight_id` INT)
RETURNS INT
    READS SQL DATA
    SQL SECURITY INVOKER
begin
 SELECT COUNT(*) FROM (
  (SELECT `fight_id` FROM `fightlist` AS `lo`
   WHERE `player_id` = `@player_id` AND `type` = 'ordered'
   ORDER BY `time` DESC LIMIT 10)
  UNION
  (SELECT `fight_id` FROM `fightlist` AS `lc`
   WHERE `player_id` = `@player_id` AND `type` = 'challenged'
   ORDER BY `time` DESC LIMIT 10)
  UNION
  (SELECT `fight_id` FROM `fightslot` AS `fs`
  WHERE `player_id` = `@player_id` AND `fight_id` = `@fight_id`)
 ) AS `total`
 WHERE `fight_id` = `@fight_id`
 INTO @`result`;
 RETURN @`result`;
end$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `delayedfight`
--

DROP TABLE IF EXISTS `delayedfight`;
CREATE TABLE `delayedfight` (
  `fight_id` int(11) NOT NULL,
  `delay_till` int NOT NULL,
  `state` text CHARACTER SET ascii COLLATE ascii_bin,
  PRIMARY KEY (`fight_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `delayedfight`
--
DROP TRIGGER IF EXISTS `delayedfight_before_delete_t`;
DELIMITER //
CREATE TRIGGER `delayedfight_before_delete_t` BEFORE DELETE ON `delayedfight`
 FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `fight`
--

DROP TABLE IF EXISTS `fight`;
CREATE TABLE `fight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refs` int(11) NOT NULL DEFAULT '1',
  `type` enum('train', 'challenge') NOT NULL,
  `time` int NULL DEFAULT NULL,
  `player_id` int(11) NOT NULL,
  `turn_limit` smallint(6) NOT NULL,
  `turn_count` smallint(6) NOT NULL,
  `turns` varbinary(2000) DEFAULT NULL,
  `result` enum('', 'limit', 'eaten', 'blocked') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `refs` (`refs`,`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `fightlist`
--

DROP TABLE IF EXISTS `fightlist`;
CREATE TABLE `fightlist` (
  `type` enum('ordered', 'challenged') NOT NULL,
  `player_id` int(11) NOT NULL,
  `time` int NOT NULL,
  `fight_id` int(11) NOT NULL,
  PRIMARY KEY (`type`, `player_id`, `time`, `fight_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Триггеры `fightlist`
--
DROP TRIGGER IF EXISTS `fightlist_after_insert_t`;
DELIMITER //
CREATE TRIGGER `fightlist_after_insert_t` AFTER INSERT ON `fightlist`
 FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` + 1
 where `id` = new.`fight_id`;
END
//
DELIMITER ;

DROP TRIGGER IF EXISTS `fightlist_before_delete_t`;
DELIMITER //
CREATE TRIGGER `fightlist_before_delete_t` BEFORE DELETE ON `fightlist`
 FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `fightslot`
--

DROP TABLE IF EXISTS `fightslot`;
CREATE TABLE `fightslot` (
  `player_id` int(11) NOT NULL,
  `index` tinyint(1) unsigned NOT NULL,
  `fight_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`player_id`, `index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `fightslot`
--
DROP TRIGGER IF EXISTS `fightslot_after_insert_t`;
DELIMITER //
CREATE TRIGGER `fightslot_after_insert_t` AFTER INSERT ON `fightslot`
FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` + 1
 where `id` = new.`fight_id`;
END
//
DELIMITER ;

DROP TRIGGER IF EXISTS `fightslot_before_delete_t`;
DELIMITER //
CREATE TRIGGER `fightslot_before_delete_t` BEFORE DELETE ON `fightslot`
FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END
//
DELIMITER ;

DROP TRIGGER IF EXISTS `fightslot_after_update_t`;
DELIMITER //
CREATE TRIGGER `fightslot_after_update_t` AFTER UPDATE ON `fightslot`
FOR EACH ROW BEGIN
 CASE WHEN new.`fight_id` <> old.`fight_id` THEN
  update `fight` set `refs` = `refs` + 1 where `id` = new.`fight_id`;
  update `fight` set `refs` = `refs` - 1 where `id` = old.`fight_id`;
 ELSE BEGIN END;
 END CASE;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `map`
--

DROP TABLE IF EXISTS `map`;
CREATE TABLE `map` (
  `snake_id` int(11) NOT NULL,
  `index` tinyint(1) unsigned NOT NULL,
  `description` varchar(1024) NOT NULL,
  `head_x` tinyint(1) unsigned NOT NULL,
  `head_y` tinyint(1) unsigned NOT NULL,
  `lines` char(98) NOT NULL,
  PRIMARY KEY (`snake_id`,`index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- СВЯЗИ ТАБЛИЦЫ `map`:
--   `snake_id`
--       `snake` -> `id`
--

-- --------------------------------------------------------

--
-- Структура таблицы `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE `player` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(40) NOT NULL,
  `login` char(30) CHARACTER SET ascii NOT NULL,
  `hash` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `salt` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `groups` int NOT NULL DEFAULT 0,
  `forum_login` char(40) NOT NULL DEFAULT '',
  `fighter_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `delayed_id` int(11) NOT NULL,
  `viewed_id` int(11) NOT NULL,
  `registered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_k` (`login`),
  UNIQUE KEY `name_k` (`name`),
  KEY `rating_k` (`rating`),
  KEY `player_ibfk_1` (`delayed_id`),
  KEY `player_ibfk_2` (`viewed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- СВЯЗИ ТАБЛИЦЫ `player`:
--   `delayed_id`
--       `fight` -> `id`
--   `viewed_id`
--       `fight` -> `id`
--

--
-- Триггеры `player`
--
DROP TRIGGER IF EXISTS `player_after_update_t`;
DELIMITER //
CREATE TRIGGER `player_after_update_t` AFTER UPDATE ON `player`
FOR EACH ROW BEGIN
 CASE WHEN new.`viewed_id` <> old.`viewed_id` THEN
  update `fight` set `refs` = `refs` + 1 where `id` = new.`viewed_id`;
  update `fight` set `refs` = `refs` - 1 where `id` = old.`viewed_id`;
 ELSE BEGIN END;
 END CASE;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `sid` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cid` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `flags` tinyint(1) NOT NULL,
  `player_id` int(11) NOT NULL,
  `sequence` int(11) NOT NULL,
  `expires` int NOT NULL,
  UNIQUE KEY `sid_k` (`sid`),
  UNIQUE KEY `cid_k` (`cid`),
  KEY `expires_k` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `skin`
--

DROP TABLE IF EXISTS `skin`;
CREATE TABLE `skin` (
  `id` int(11) NOT NULL,
  `name` char(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `skin`
--

INSERT INTO `skin` (`id`, `name`) VALUES
(1, '- по умолчанию -');

-- --------------------------------------------------------

--
-- Структура таблицы `snake`
--

DROP TABLE IF EXISTS `snake`;
CREATE TABLE `snake` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `base_id` int(11) NOT NULL,
  `refs` int(11) NOT NULL DEFAULT '1',
  `current` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `player_id` int(11) NOT NULL,
  `name` char(40) NOT NULL,
  `type` char(1) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `templates` char(27) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `base_id_current_k` (`base_id`,`current`),
  KEY `player_id_current_k` (`player_id`,`current`),
  KEY `type_current_k` (`type`,`current`),
  KEY `name_current_k` (`name`,`current`),
  KEY `skin_id_k` (`skin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- СВЯЗИ ТАБЛИЦЫ `snake`:
--   `player_id`
--       `player` -> `id`
--   `skin_id`
--       `skin` -> `id`
--

-- --------------------------------------------------------

--
-- Структура таблицы `snakestat`
--

DROP TABLE IF EXISTS `snakestat`;
CREATE TABLE `snakestat` (
  `fight_id` int(11) NOT NULL,
  `index` tinyint(1) NOT NULL,
  `snake_id` int(11) NOT NULL,
  `result` enum('', 'free', 'eaten', 'blocked') NOT NULL,
  `length` tinyint(4) NOT NULL,
  `pre_rating` int(11) DEFAULT NULL,
  `post_rating` int(11) DEFAULT NULL,
  `debug` varchar(1000) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`fight_id`,`index`),
  KEY `snake_id_k` (`snake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- СВЯЗИ ТАБЛИЦЫ `snakestat`:
--   `snake_id`
--       `snake` -> `id`
--   `fight_id`
--       `fight` -> `id`
--

--
-- Триггеры `snakestat`
--
DROP TRIGGER IF EXISTS `snakestat_after_insert_t`;
DELIMITER //
CREATE TRIGGER `snakestat_after_insert_t` AFTER INSERT ON `snakestat`
 FOR EACH ROW BEGIN
 update `snake` set `refs` = `refs` + 1
 where `id` = new.`snake_id`;
END
//
DELIMITER ;

DROP TRIGGER IF EXISTS `snakestat_before_delete_t`;
DELIMITER //
CREATE TRIGGER `snakestat_before_delete_t` BEFORE DELETE ON `snakestat`
 FOR EACH ROW BEGIN
 update `snake` set `refs` = `refs` - 1
 where `id` = old.`snake_id`;
END
//
DELIMITER ;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `map`
--
ALTER TABLE `map`
  ADD CONSTRAINT `map_ibfk_1` FOREIGN KEY (`snake_id`) REFERENCES `snake` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `snakestat`
--
ALTER TABLE `snakestat`
  ADD CONSTRAINT `snakestat_ibfk_1` FOREIGN KEY (`fight_id`) REFERENCES `fight` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;

-- phpMyAdmin SQL Dump
-- version 3.5.6
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Май 23 2013 г., 20:07
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
begin
 delete from `snake` where `refs` <= 0;
 delete from `fight` where `refs` <= 0;
end$$

DROP PROCEDURE IF EXISTS `update_fight_list`$$
CREATE PROCEDURE `update_fight_list`(
 IN `@player_id` INT, IN `@list_type` ENUM('ordered', 'challenged'), IN `@fight_id` INT
)
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

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `delayedfight`
--

DROP TABLE IF EXISTS `delayedfight`;
CREATE TABLE `delayedfight` (
  `fight_id` int(11) NOT NULL,
  `delay_till` TIMESTAMP NULL DEFAULT NULL,
  `state` text CHARACTER SET ascii COLLATE ascii_bin,
  PRIMARY KEY (`fight_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `fightlist`
--

DROP TABLE IF EXISTS `fightlist`;
CREATE TABLE `fightlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `type` char(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fight_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Триггеры `fightlist`
--
DROP TRIGGER IF EXISTS `fightlist_before_delete_t`;
DELIMITER $$
CREATE TRIGGER `fightlist_before_delete_t` BEFORE DELETE ON `fightlist`
 FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `fight`
--

DROP TABLE IF EXISTS `fight`;
CREATE TABLE `fight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refs` int(11) NOT NULL DEFAULT '1',
  `type` char(9) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `time` timestamp NULL DEFAULT NULL,
  `ordered` int(11) NOT NULL,
  `turn_limit` smallint(6) NOT NULL,
  `turn_count` smallint(6) NOT NULL,
  `turns` varbinary(2000),
  `result` char(7) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refs` (`refs`,`ordered`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Триггеры `fight`
--
DROP TRIGGER IF EXISTS `fight_before_delete_t`;
DELIMITER $$
CREATE TRIGGER `fight_before_delete_t` BEFORE DELETE ON `fight`
 FOR EACH ROW BEGIN
 update `snake` set `refs` = `refs` - 1
 where `id` in (SELECT `snake_id` FROM `snakestat` WHERE `fight_id` = old.`id`);
END$$
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
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `fightstot`
--
DROP TRIGGER IF EXISTS `fightslot_before_delete_t`;
DELIMITER $$
CREATE TRIGGER `fightslot_before_delete_t` BEFORE DELETE ON `fightslot`
 FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END$$
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
  `name` char(30) NOT NULL,
  `login` char(16) CHARACTER SET ascii NOT NULL,
  `hash` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `salt` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT '0',
  `fighter_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
	`delayed_id` int(11) DEFAULT NULL,
	`viewed_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_k` (`login`),
  UNIQUE KEY `name_k` (`name`),
  KEY `rating_k` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `sid` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cid` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `player_id` int(11) NOT NULL,
  `sequence` int(11) NOT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
--   `skin`
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
  `result` char(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
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
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `player`
--
ALTER TABLE `player`
  ADD CONSTRAINT `player_ibfk_1` FOREIGN KEY (`delayed_id`) REFERENCES `fight` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_ibfk_2` FOREIGN KEY (`viewed_id`) REFERENCES `fight` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `map`
--
ALTER TABLE `map`
  ADD CONSTRAINT `map_ibfk_1` FOREIGN KEY (`snake_id`) REFERENCES `snake` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `snake`
--
ALTER TABLE `snake`
  ADD CONSTRAINT `snake_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `snake_ibfk_2` FOREIGN KEY (`skin_id`) REFERENCES `skin` (`id`);

--
-- Ограничения внешнего ключа таблицы `snakestat`
--
ALTER TABLE `snakestat`
  ADD CONSTRAINT `snakestat_ibfk_2` FOREIGN KEY (`snake_id`) REFERENCES `snake` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `snakestat_ibfk_1` FOREIGN KEY (`fight_id`) REFERENCES `fight` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;

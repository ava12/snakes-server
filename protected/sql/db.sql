-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Фев 24 2015 г., 16:03
-- Версия сервера: 5.6.21
-- Версия PHP: 5.6.3

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `snakes_denorm`
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

--
-- Функции
--
DROP FUNCTION IF EXISTS `can_view_fight`$$
CREATE FUNCTION `can_view_fight`(`@player_id` INT, `@fight_id` INT) RETURNS int(11)
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
CREATE TABLE IF NOT EXISTS `delayedfight` (
  `fight_id` int(11) NOT NULL,
  `delay_till` int(11) NOT NULL,
  `data` text
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
CREATE TABLE IF NOT EXISTS `fight` (
`id` int(11) NOT NULL,
  `refs` int(11) NOT NULL DEFAULT '1',
  `type` enum('train','challenge') NOT NULL,
  `time` int(11) DEFAULT NULL,
  `player_id` int(11) NOT NULL,
  `turn_limit` smallint(6) NOT NULL,
  `result` enum('','limit','eaten','blocked') NOT NULL,
  `data` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `fightlist`
--

DROP TABLE IF EXISTS `fightlist`;
CREATE TABLE IF NOT EXISTS `fightlist` (
  `type` enum('ordered','challenged') NOT NULL,
  `player_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `fight_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
CREATE TABLE IF NOT EXISTS `fightslot` (
  `player_id` int(11) NOT NULL,
  `index` tinyint(1) unsigned NOT NULL,
  `fight_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
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
DROP TRIGGER IF EXISTS `fightslot_before_delete_t`;
DELIMITER //
CREATE TRIGGER `fightslot_before_delete_t` BEFORE DELETE ON `fightslot`
 FOR EACH ROW BEGIN
 update `fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
`id` int(11) NOT NULL,
  `name` char(40) NOT NULL,
  `login` char(30) CHARACTER SET ascii NOT NULL,
  `hash` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `salt` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT '0',
  `groups` int(11) NOT NULL DEFAULT '0',
  `forum_login` char(40) NOT NULL DEFAULT '',
  `fighter_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `delayed_id` int(11) NOT NULL,
  `viewed_id` int(11) NOT NULL,
  `registered` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
CREATE TABLE IF NOT EXISTS `session` (
  `sid` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cid` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `flags` tinyint(1) NOT NULL,
  `player_id` int(11) NOT NULL,
  `sequence` int(11) NOT NULL,
  `expires` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `skin`
--

DROP TABLE IF EXISTS `skin`;
CREATE TABLE IF NOT EXISTS `skin` (
  `id` int(11) NOT NULL,
  `name` char(40) NOT NULL
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
CREATE TABLE IF NOT EXISTS `snake` (
`id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `name` char(40) NOT NULL,
  `type` char(1) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `data` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `delayedfight`
--
ALTER TABLE `delayedfight`
 ADD PRIMARY KEY (`fight_id`);

--
-- Индексы таблицы `fight`
--
ALTER TABLE `fight`
 ADD PRIMARY KEY (`id`), ADD KEY `refs` (`refs`,`player_id`);

--
-- Индексы таблицы `fightlist`
--
ALTER TABLE `fightlist`
 ADD PRIMARY KEY (`type`,`player_id`,`time`,`fight_id`);

--
-- Индексы таблицы `fightslot`
--
ALTER TABLE `fightslot`
 ADD PRIMARY KEY (`player_id`,`index`);

--
-- Индексы таблицы `player`
--
ALTER TABLE `player`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `login_k` (`login`), ADD UNIQUE KEY `name_k` (`name`), ADD KEY `rating_k` (`rating`), ADD KEY `player_ibfk_1` (`delayed_id`), ADD KEY `player_ibfk_2` (`viewed_id`);

--
-- Индексы таблицы `session`
--
ALTER TABLE `session`
 ADD UNIQUE KEY `sid_k` (`sid`), ADD UNIQUE KEY `cid_k` (`cid`), ADD KEY `expires_k` (`expires`);

--
-- Индексы таблицы `skin`
--
ALTER TABLE `skin`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `snake`
--
ALTER TABLE `snake`
 ADD PRIMARY KEY (`id`), ADD KEY `player_id_current_k` (`player_id`), ADD KEY `type_current_k` (`type`), ADD KEY `name_current_k` (`name`), ADD KEY `skin_id_k` (`skin_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `fight`
--
ALTER TABLE `fight`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `player`
--
ALTER TABLE `player`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `snake`
--
ALTER TABLE `snake`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

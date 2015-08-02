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
-- База данных: `snakes`
--

DELIMITER $$
--
-- Процедуры
--
DROP PROCEDURE IF EXISTS `snakes_delete_zero_refs`$$
CREATE PROCEDURE `snakes_delete_zero_refs`()
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
begin
 delete from `snakes_fight` where `refs` <= 0;
 delete from `snakes_session` where `expires` >= NOW();
end$$

DROP PROCEDURE IF EXISTS `snakes_update_fight_list`$$
CREATE PROCEDURE `snakes_update_fight_list`(IN `@list_type` ENUM('ordered','challenged'), IN `@player_id` INT, IN `@fight_id` INT, IN `@time` INT)
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
begin
 insert ignore into `snakes_fightlist` (`player_id`, `type`, `fight_id`, `time`)
 values (`@player_id`, `@list_type`, `@fight_id`, `@time`);
 delete from `snakes_fightlist`
 where `player_id` = `@player_id` and `type` = `@list_type` and `fight_id` in (
  select * from (
   select `fight_id` from `snakes_fightlist`
   inner join `snakes_fight` on `snakes_fightlist`.`fight_id` = `snakes_fight`.`id`
   where `snakes_fightlist`.`player_id` = `@player_id` and `snakes_fightlist`.`type` = `@list_type`
   order by `snakes_fight`.`time` desc limit 1000 offset 10
	) as t
 );
end$$

--
-- Функции
--
DROP FUNCTION IF EXISTS `snakes_can_view_fight`$$
CREATE FUNCTION `snakes_can_view_fight`(`@player_id` INT, `@fight_id` INT) RETURNS int(11)
    READS SQL DATA
    SQL SECURITY INVOKER
begin
SELECT COUNT(*) FROM (
  (SELECT `fight_id` FROM `snakes_fightlist` AS `lo`
   WHERE `player_id` = `@player_id` AND `type` = 'ordered'
   ORDER BY `time` DESC LIMIT 10)
  UNION
  (SELECT `fight_id` FROM `snakes_fightlist` AS `lc`
   WHERE `player_id` = `@player_id` AND `type` = 'challenged'
   ORDER BY `time` DESC LIMIT 10)
  UNION
  (SELECT `fight_id` FROM `snakes_fightslot` AS `fs`
  WHERE `player_id` = `@player_id` AND `fight_id` = `@fight_id`)
 ) AS `total`
 WHERE `fight_id` = `@fight_id`
 INTO @`result`;
 RETURN @`result`;
end$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `snakes_delayedfight`
--

DROP TABLE IF EXISTS `snakes_delayedfight`;
CREATE TABLE IF NOT EXISTS `snakes_delayedfight` (
  `fight_id` int(11) NOT NULL,
  `delay_till` int(11) NOT NULL,
  `data` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `snakes_delayedfight`
--
DROP TRIGGER IF EXISTS `snakes_delayedfight_before_delete_t`;
DELIMITER $$
CREATE TRIGGER `snakes_delayedfight_before_delete_t` BEFORE DELETE ON `snakes_delayedfight`
 FOR EACH ROW BEGIN
 update `snakes_fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `snakes_fight`
--

DROP TABLE IF EXISTS `snakes_fight`;
CREATE TABLE IF NOT EXISTS `snakes_fight` (
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
-- Структура таблицы `snakes_fightlist`
--

DROP TABLE IF EXISTS `snakes_fightlist`;
CREATE TABLE IF NOT EXISTS `snakes_fightlist` (
  `type` enum('ordered','challenged') NOT NULL,
  `player_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `fight_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `snakes_fightlist`
--
DROP TRIGGER IF EXISTS `snakes_fightlist_after_insert_t`;
DELIMITER $$
CREATE TRIGGER `snakes_fightlist_after_insert_t` AFTER INSERT ON `snakes_fightlist`
 FOR EACH ROW BEGIN
 update `snakes_fight` set `refs` = `refs` + 1
 where `id` = new.`fight_id`;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `snakes_fightlist_before_delete_t`;
DELIMITER $$
CREATE TRIGGER `snakes_fightlist_before_delete_t` BEFORE DELETE ON `snakes_fightlist`
 FOR EACH ROW BEGIN
 update `snakes_fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `snakes_fightslot`
--

DROP TABLE IF EXISTS `snakes_fightslot`;
CREATE TABLE IF NOT EXISTS `snakes_fightslot` (
  `player_id` int(11) NOT NULL,
  `index` tinyint(1) unsigned NOT NULL,
  `fight_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `snakes_fightslot`
--
DROP TRIGGER IF EXISTS `snakes_fightslot_after_insert_t`;
DELIMITER $$
CREATE TRIGGER `snakes_fightslot_after_insert_t` AFTER INSERT ON `snakes_fightslot`
 FOR EACH ROW BEGIN
 update `snakes_fight` set `refs` = `refs` + 1
 where `id` = new.`fight_id`;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `fightslot_after_update_t`;
DELIMITER $$
CREATE TRIGGER `fightslot_after_update_t` AFTER UPDATE ON `snakes_fightslot`
 FOR EACH ROW BEGIN
 CASE WHEN new.`fight_id` <> old.`fight_id` THEN
  update `snakes_fight` set `refs` = `refs` + 1 where `id` = new.`fight_id`;
  update `snakes_fight` set `refs` = `refs` - 1 where `id` = old.`fight_id`;
 ELSE BEGIN END;
 END CASE;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `fightslot_before_delete_t`;
DELIMITER $$
CREATE TRIGGER `fightslot_before_delete_t` BEFORE DELETE ON `snakes_fightslot`
 FOR EACH ROW BEGIN
 update `snakes_fight` set `refs` = `refs` - 1
 where `id` = old.`fight_id`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `snakes_player`
--

DROP TABLE IF EXISTS `snakes_player`;
CREATE TABLE IF NOT EXISTS `snakes_player` (
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
-- Триггеры `snakes_player`
--
DROP TRIGGER IF EXISTS `snakes_player_after_update_t`;
DELIMITER $$
CREATE TRIGGER `snakes_player_after_update_t` AFTER UPDATE ON `snakes_player`
 FOR EACH ROW BEGIN
 CASE WHEN new.`viewed_id` <> old.`viewed_id` THEN
  update `snakes_fight` set `refs` = `refs` + 1 where `id` = new.`viewed_id`;
  update `snakes_fight` set `refs` = `refs` - 1 where `id` = old.`viewed_id`;
 ELSE BEGIN END;
 END CASE;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `snakes_session`
--

DROP TABLE IF EXISTS `snakes_session`;
CREATE TABLE IF NOT EXISTS `snakes_session` (
  `sid` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cid` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `flags` tinyint(1) NOT NULL,
  `player_id` int(11) NOT NULL,
  `sequence` int(11) NOT NULL,
  `expires` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `snakes_skin`
--

DROP TABLE IF EXISTS `snakes_skin`;
CREATE TABLE IF NOT EXISTS `snakes_skin` (
  `id` int(11) NOT NULL,
  `name` char(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `snakes_snake`
--

DROP TABLE IF EXISTS `snakes_snake`;
CREATE TABLE IF NOT EXISTS `snakes_snake` (
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
-- Индексы таблицы `snakes_delayedfight`
--
ALTER TABLE `snakes_delayedfight`
 ADD PRIMARY KEY (`fight_id`);

--
-- Индексы таблицы `snakes_fight`
--
ALTER TABLE `snakes_fight`
 ADD PRIMARY KEY (`id`), ADD KEY `refs` (`refs`,`player_id`);

--
-- Индексы таблицы `snakes_fightlist`
--
ALTER TABLE `snakes_fightlist`
 ADD PRIMARY KEY (`type`,`player_id`,`time`,`fight_id`);

--
-- Индексы таблицы `snakes_fightslot`
--
ALTER TABLE `snakes_fightslot`
 ADD PRIMARY KEY (`player_id`,`index`);

--
-- Индексы таблицы `snakes_player`
--
ALTER TABLE `snakes_player`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `login_k` (`login`), ADD UNIQUE KEY `name_k` (`name`), ADD KEY `rating_k` (`rating`), ADD KEY `player_ibfk_1` (`delayed_id`), ADD KEY `player_ibfk_2` (`viewed_id`);

--
-- Индексы таблицы `snakes_session`
--
ALTER TABLE `snakes_session`
 ADD UNIQUE KEY `sid_k` (`sid`), ADD UNIQUE KEY `cid_k` (`cid`), ADD KEY `expires_k` (`expires`);

--
-- Индексы таблицы `snakes_skin`
--
ALTER TABLE `snakes_skin`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `snakes_snake`
--
ALTER TABLE `snakes_snake`
 ADD PRIMARY KEY (`id`), ADD KEY `player_id_current_k` (`player_id`), ADD KEY `type_current_k` (`type`), ADD KEY `name_current_k` (`name`), ADD KEY `skin_id_k` (`skin_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `snakes_fight`
--
ALTER TABLE `snakes_fight`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `snakes_player`
--
ALTER TABLE `snakes_player`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `snakes_snake`
--
ALTER TABLE `snakes_snake`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

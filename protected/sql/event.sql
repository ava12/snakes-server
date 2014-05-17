-- использовать вместо cron-чистильщика, если в БД разрешены события

DELIMITER $$
--
-- События
--
DROP EVENT IF EXISTS `delete_zero_refs_e`$$
CREATE EVENT `delete_zero_refs_e` ON SCHEDULE EVERY 1 HOUR DO call `delete_zero_refs`()$$

DELIMITER ;

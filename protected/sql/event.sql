-- использовать вместо cron-чистильщика, если в БД разрешены события

DELIMITER $$
--
-- События
--
DROP EVENT `snakes_delete_zero_refs_e`$$
CREATE EVENT `snakes_delete_zero_refs_e` ON SCHEDULE EVERY 1 HOUR
 ON COMPLETION NOT PRESERVE ENABLE DO
 call `snakes_delete_zero_refs`()$$

DELIMITER ;

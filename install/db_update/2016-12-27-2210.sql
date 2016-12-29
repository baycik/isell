DROP TABLE `replication_log`;
DROP TABLE `stock_checkout_entries`;
DROP TABLE `stock_checkout_list`;


ALTER TABLE `acc_trans` 
DROP FOREIGN KEY `FK_acc_artcleid`;
ALTER TABLE `acc_trans` 
DROP COLUMN `article_id`,
DROP INDEX `FK_acc_artcleid_idx` ;

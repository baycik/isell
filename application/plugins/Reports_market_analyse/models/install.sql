CREATE TABLE `plugin_market_rpt_list` (
  `report_id` INT NOT NULL AUTO_INCREMENT,
  `idate` VARCHAR(45) NULL,
  `fdate` VARCHAR(45) NULL,
  `pcomp_id` INT NULL,
  `comment` VARCHAR(45) NULL,
  PRIMARY KEY (`report_id`));

CREATE TABLE `plugin_market_rpt_entries` (
  `report_id` int(11) NOT NULL,
  `product_code` varchar(45) DEFAULT NULL COMMENT 'Код товара',
  `article` text NOT NULL,
  `product_name` varchar(255) DEFAULT NULL COMMENT 'Название Рус.',
  `analyse_type` varchar(45) DEFAULT NULL COMMENT 'Тип',
  `analyse_group` varchar(45) DEFAULT NULL COMMENT 'Группа',
  `store_code` text NOT NULL,
  `sold` text NOT NULL,
  `leftover` text NOT NULL,
  `avg_price` double(19,2) DEFAULT NULL,
  `group_by` text NOT NULL,
  `sold_sum` double DEFAULT NULL,
  `leftover_sum` double DEFAULT NULL,
  KEY `product_code` (`product_code`,`report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


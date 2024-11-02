CREATE TABLE `plugin_stock_layout_cells` (
  `cell_id` int NOT NULL AUTO_INCREMENT,
  `cell_realm` varchar(45) DEFAULT ' ',
  `cell_sector` varchar(45) DEFAULT NULL,
  `cell_level` varchar(45) DEFAULT NULL,
  `cell_number` varchar(45) DEFAULT NULL,
  `cell_width` int DEFAULT NULL,
  `cell_height` int DEFAULT NULL,
  `cell_depth` int DEFAULT NULL,
  `cell_volume` float GENERATED ALWAYS AS ((((`cell_width` * `cell_height`) * `cell_depth`) / 1000000)) STORED,
  `cell_comment` varchar(45) DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_valid` tinyint GENERATED ALWAYS AS (((`cell_sector` is not null) and (`cell_level` is not null) and (`cell_number` is not null) and (`cell_volume` is not null))) VIRTUAL,
  PRIMARY KEY (`cell_id`),
  UNIQUE KEY `psc_uq` (`cell_sector`,`cell_level`,`cell_number`,`cell_realm`) /*!80000 INVISIBLE */
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4;


CREATE TABLE `plugin_stock_layout_links` (
  `link_id` int NOT NULL AUTO_INCREMENT,
  `cell_id` int NOT NULL,
  `product_id` int NOT NULL,
  `stored_quantity` decimal(10,2) DEFAULT NULL,
  `stored_volume` float DEFAULT NULL,
  `allocated_volume` float DEFAULT NULL COMMENT 'Total allocated volume of product',
  `sub_cell_volume` float DEFAULT NULL,
  PRIMARY KEY (`link_id`),
  UNIQUE KEY `psc_uq` (`cell_id`,`product_id`),
  KEY `psc_pid_idx` (`product_id`),
  CONSTRAINT `psc_cid` FOREIGN KEY (`cell_id`) REFERENCES `plugin_stock_layout_cells` (`cell_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `psc_pid` FOREIGN KEY (`product_id`) REFERENCES `prod_list` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4;

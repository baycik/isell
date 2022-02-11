/**
 * Author:  AdamHider
 */
CREATE TABLE `attribute_list` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_name` varchar(45) NOT NULL,
  `attribute_unit` varchar(225) NOT NULL,
  `attribute_prefix` varchar(45) NOT NULL,
  PRIMARY KEY (`attribute_id`),
  UNIQUE KEY `attribute_id_UNIQUE` (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `attribute_values` (
  `attribute_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_value` varchar(225) DEFAULT NULL,
  `attribute_value_hash` varchar(32) GENERATED ALWAYS AS (md5(concat(`attribute_id`,'|',`attribute_value`))) STORED,
  PRIMARY KEY (`attribute_id`,`product_id`),
  KEY `attribute_value_hash_index` (`attribute_value_hash`),
  KEY `index3` (`product_id`),
  CONSTRAINT `aid` FOREIGN KEY (`attribute_id`) REFERENCES `attribute_list` (`attribute_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `pid` FOREIGN KEY (`product_id`) REFERENCES `prod_list` (`product_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


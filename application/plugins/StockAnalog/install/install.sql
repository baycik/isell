CREATE TABLE `plugin_analog_list` (
  `analog_id` int(11) NOT NULL AUTO_INCREMENT,
  `analog_group_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`analog_id`),
  UNIQUE KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

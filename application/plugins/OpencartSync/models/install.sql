CREATE TABLE `plugin_opencart_sync_list` (
  `remote_product_id` int(11) DEFAULT NULL,
  `remote_model` varchar(45) DEFAULT NULL,
  `remote_field_hash` varchar(32) DEFAULT NULL,
  `remote_img_hash` varchar(32) DEFAULT NULL,
  `remote_img_time` int(11) DEFAULT NULL,
  `remote_img_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

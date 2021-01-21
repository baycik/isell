CREATE TABLE `plugin_campaign_bonus_periods` (
  `campaign_bonus_period_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_bonus_id` int(11) DEFAULT NULL,
  `period_year` int(11) DEFAULT NULL,
  `period_quarter` int(11) DEFAULT NULL,
  `period_month` int(11) DEFAULT NULL,
  `period_plan1` float NOT NULL,
  `period_plan2` float NOT NULL,
  `period_plan3` float NOT NULL,
  `period_reward1` float NOT NULL,
  `period_reward2` float NOT NULL,
  `period_reward3` float NOT NULL,
  PRIMARY KEY (`campaign_bonus_period_id`),
  UNIQUE KEY `period_unique` (`campaign_bonus_id`,`period_year`,`period_quarter`,`period_month`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `plugin_campaign_list` (
  `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
  `liable_user_id` int(11) DEFAULT NULL,
  `campaign_name` varchar(45) DEFAULT NULL,
  `subject_path_include` varchar(255) DEFAULT NULL,
  `subject_path_exclude` varchar(255) DEFAULT NULL,
  `subject_manager_include` varchar(45) DEFAULT NULL,
  `subject_manager_exclude` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`campaign_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='';

CREATE TABLE `plugin_campaign_bonus` (
  `campaign_bonus_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) DEFAULT NULL,
  `campaign_bonus_ratio1` float DEFAULT NULL,
  `campaign_bonus_ratio2` float DEFAULT NULL,
  `campaign_bonus_ratio3` float DEFAULT NULL,
  `campaign_start_at` datetime DEFAULT NULL,
  `campaign_finish_at` datetime DEFAULT NULL,
  `campaign_grouping_interval` varchar(45) DEFAULT NULL,
  `campaign_queue` tinyint(4) DEFAULT '1',
  `product_category_id` int(11) DEFAULT NULL,
  `product_category_path` varchar(255) DEFAULT NULL,
  `product_brand_filter` varchar(255) DEFAULT NULL,
  `product_type_filter` varchar(255) DEFAULT NULL,
  `product_class_filter` varchar(2) DEFAULT NULL,
  `bonus_type` varchar(45) DEFAULT NULL,
  `bonus_visibility` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`campaign_bonus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

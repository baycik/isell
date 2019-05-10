/**
 * Author:  admin
 * Created: May 9, 2019
 */

CREATE TABLE `price_breakeven` (
  `breakeven_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `breakeven_ratio` float DEFAULT NULL,
  `breakeven_base` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`breakeven_rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

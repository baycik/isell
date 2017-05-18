/**
 * Author:  baycik
 * Created: May 18, 2017
 */
START TRANSACTION;

ALTER TABLE `prod_list` 
ADD UNIQUE INDEX `index2` (`product_code` ASC);


ALTER TABLE `prod_list` 
DROP PRIMARY KEY;


ALTER TABLE `prod_list` 
ADD COLUMN `product_id` INT NOT NULL AUTO_INCREMENT FIRST,
ADD PRIMARY KEY (`product_id`);

COMMIT;
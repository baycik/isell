/**
 * Author:  admin
 * Created: May 16, 2019
 */

ALTER TABLE `companies_list` 
ADD COLUMN `skip_breakeven_check` TINYINT NULL AFTER `is_active`;

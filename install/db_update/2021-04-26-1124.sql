USE `isell_db`;
DROP function IF EXISTS `GET_PRICE`;

DELIMITER $$
USE `isell_db`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `GET_PRICE`(_product_code VARCHAR(45)  CHARSET utf8,pcomp_id INT,usd_ratio DOUBLE) RETURNS varchar(45) CHARSET utf8
    DETERMINISTIC
BEGIN
DECLARE _discount DOUBLE;
DECLARE _price_label VARCHAR(45) CHARSET utf8;
DECLARE _price DOUBLE;
SELECT 
	COALESCE(price_label,'') INTO _price_label 
FROM 
	companies_list 
WHERE company_id=pcomp_id;

SELECT 
	discount INTO _discount 
FROM 
	companies_discounts cd 
JOIN 
	stock_tree st ON st.top_id=cd.branch_id 
JOIN 
	stock_entries se ON se.parent_id=st.branch_id 
WHERE 
	se.product_code=_product_code AND company_id=pcomp_id;
        
SELECT 
	sell*IF(curr_code='USD',usd_ratio,1)*IF(_discount,_discount,1) INTO _price 
FROM 
	price_list 
WHERE 
	product_code=_product_code AND (label=_price_label OR label='')
ORDER BY label=_price_label DESC
LIMIT 1;


RETURN ROUND(_price,2);
END$$

DELIMITER ;


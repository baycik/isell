DELIMITER $$
CREATE DEFINER=`root`@`localhost` FUNCTION `GET_PRICE`(_product_code VARCHAR(45)  CHARSET utf8,pcomp_id INT,usd_ratio DOUBLE) RETURNS decimal(10,2)
    DETERMINISTIC
BEGIN
DECLARE _discount FLOAT;
DECLARE _round_to  FLOAT;
DECLARE _price_label VARCHAR(45) CHARSET utf8;
DECLARE _price DECIMAL(10,2);

SELECT 
	COALESCE(price_label,'') INTO _price_label 
FROM 
	companies_list 
WHERE company_id=pcomp_id;
        
SELECT 
	discount,round_to INTO _discount,_round_to
FROM 
	companies_discounts cd 
JOIN 
	stock_tree st ON st.path_id LIKE CONCAT('%/',cd.branch_id,'/%')
JOIN  
	stock_entries se ON se.parent_id=st.branch_id 
WHERE 
	 se.product_code=_product_code AND company_id=pcomp_id;
        
IF (_discount=0 OR _discount IS NULL) THEN
	SELECT 
		discount,round_to INTO _discount,_round_to 
	FROM 
		companies_discounts cd 
	JOIN
		prod_list pl ON cd.analyse_brand=pl.analyse_brand
	WHERE 
		pl.product_code=_product_code AND company_id=pcomp_id;
END IF;

IF (_discount=0 OR _discount IS NULL) THEN
	SELECT 
		discount,round_to INTO _discount,_round_to 
	FROM 
		companies_discounts cd 
	WHERE 
		branch_id IS NULL AND analyse_brand IS NULL AND company_id=pcomp_id;
END IF;

IF (_round_to=0 OR _round_to IS NULL) THEN
	SET _round_to=0.01;
END IF;

SELECT 
	ROUND(sell*IF(curr_code='USD',usd_ratio,1)*IF(_discount,_discount,1)/_round_to)*_round_to INTO _price 
FROM 
	price_list 
WHERE 
	product_code=_product_code AND (label=_price_label OR label='')
ORDER BY label=_price_label DESC
LIMIT 1;

RETURN IF(_price>0,_price,_round_to);
END$$
DELIMITER ;

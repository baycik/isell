
DROP function IF EXISTS `GET_BREAKEVEN_PRICE`;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` FUNCTION `GET_BREAKEVEN_PRICE`(_product_code VARCHAR(45)  CHARSET utf8, pcomp_id INT, usd_ratio DOUBLE, _self_price DOUBLE) RETURNS float
    DETERMINISTIC
BEGIN
	DECLARE _breakeven_ratio DOUBLE;
    DECLARE _breakeven_base VARCHAR(30) CHARSET utf8;
	DECLARE _price_label VARCHAR(45) CHARSET utf8;
	DECLARE _price DOUBLE;
    DECLARE _promo_price DOUBLE;

	SELECT 
		COALESCE(price_label,'') INTO _price_label 
	FROM 
		companies_list 
	WHERE company_id=pcomp_id;

	SELECT 
		breakeven_ratio, breakeven_base INTO _breakeven_ratio, _breakeven_base
	FROM 
		price_breakeven pb 
	JOIN 
		stock_tree st ON st.top_id=pb.branch_id 
	JOIN 
		stock_entries se ON se.parent_id=st.branch_id 
	WHERE 
		se.product_code=_product_code AND company_id=pcomp_id
        OR se.product_code=_product_code
	ORDER BY company_id=pcomp_id DESC, NOT company_id DESC
	LIMIT 1; 
    
	SELECT 
		sell*IF(curr_code='USD',usd_ratio,1) INTO _promo_price
	FROM 
		price_list 
	WHERE
		product_code=_product_code AND label='PROMO';
    IF _promo_price THEN
		RETURN _promo_price;
	END IF;
    
    
CASE
	WHEN _breakeven_base='self_price' THEN
		SELECT _self_price*IF(_breakeven_ratio,_breakeven_ratio/100+1,1) INTO _price;
	ELSE
		SELECT 
			buy*IF(curr_code='USD',usd_ratio,1)*IF(_breakeven_ratio,_breakeven_ratio/100+1,1) INTO _price 
		FROM 
			price_list 
		WHERE 
			product_code=_product_code AND (label=_price_label OR label='')
		ORDER BY buy<>0 DESC,label=_price_label DESC
		LIMIT 1;
END CASE;
	RETURN _price;
END$$

DELIMITER ;


DELIMITER $$
CREATE DEFINER=`root`@`localhost` FUNCTION `GET_SELL_PRICE`(_product_code VARCHAR(45)  CHARSET utf8,pcomp_id INT,usd_ratio DOUBLE) RETURNS varchar(45) CHARSET utf8
    DETERMINISTIC
BEGIN
    DECLARE _promo_price DOUBLE;

	SELECT 
		sell*IF(curr_code='USD',usd_ratio,1) INTO _promo_price
	FROM 
		price_list 
	WHERE
		product_code=_product_code AND label='PROMO';
		
	IF _promo_price THEN
		RETURN ROUND(_promo_price,2);
	END IF;
    
    RETURN GET_PRICE(_product_code,pcomp_id,usd_ratio);
END$$
DELIMITER ;
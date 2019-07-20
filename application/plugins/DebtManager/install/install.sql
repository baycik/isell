DROP function IF EXISTS `GET_PARTLY_PAYED`;
DELIMITER $$
CREATE DEFINER=`root`@`localhost` FUNCTION `GET_PARTLY_PAYED`( _active_company_id INT, _passive_company_id INT, _acc_code VARCHAR(45) ) RETURNS double
    DETERMINISTIC
BEGIN

DECLARE _partly_payed DOUBLE;

SET @total:= 0.00, @unpayed:= 0.00;

SELECT ROUND(@total-@unpayed,2) INTO _partly_payed 
FROM (
	SELECT 
		@total:=@total+IF(acc_credit_code = _acc_code, - amount, + amount),
		@unpayed:=@unpayed+IF (trans_status = 1, amount, 0)
	FROM
		acc_trans
	WHERE
		active_company_id = _active_company_id
		AND passive_company_id = _passive_company_id
		AND (acc_credit_code = _acc_code OR acc_debit_code = _acc_code)
) 
magic_table LIMIT 1;
    
RETURN _partly_payed;   
END$$

DELIMITER ;


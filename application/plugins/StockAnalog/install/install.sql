CREATE TABLE `plugin_analog_list` (
  `analog_id` int(11) NOT NULL AUTO_INCREMENT,
  `analog_group_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`analog_id`),
  UNIQUE KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DELIMITER $$
CREATE DEFINER=`root`@`localhost` FUNCTION `PLUGIN_CHK_ANALOG`( _row_status VARCHAR(100) CHARSET utf8, _doc_type INT, _product_id INT, _product_quantity FLOAT, _analyse_class CHAR ) RETURNS varchar(255) CHARSET utf8
BEGIN
DECLARE _status_severity VARCHAR(3);
DECLARE _status_msg VARCHAR(100) CHARSET utf8;
DECLARE _analog_code VARCHAR(100) CHARSET utf8;
DECLARE _analog_name VARCHAR(100) CHARSET utf8;
DECLARE _analog_priority BOOL;
DECLARE _analog_quantity VARCHAR(10) CHARSET utf8;

CASE
	WHEN _doc_type=1 THEN
		SET _status_msg=_status_msg;
	WHEN _doc_type=2 THEN
		SET _product_quantity=0;
	ELSE
		RETURN _row_status;
END CASE;

SELECT RTRIM(SUBSTRING(_row_status,1,3)), SUBSTRING(_row_status,POSITION(" " IN _row_status)) INTO _status_severity,_status_msg;

SELECT 
	product_code,
	ru,
	_analyse_class<analyse_class,
    CONCAT(se.product_quantity,pl.product_unit)
INTO
	_analog_code,
	_analog_name,
	_analog_priority,
    _analog_quantity
FROM 
	plugin_analog_list pal1
		JOIN
	plugin_analog_list pal2 USING(analog_group_id)
		JOIN
	prod_list pl ON pal2.product_id=pl.product_id
		JOIN
	stock_entries se USING(product_code)
WHERE 
	pal1.product_id=_product_id
	AND pal2.product_id<>_product_id
	AND se.product_quantity>=_product_quantity
ORDER BY analyse_class DESC
LIMIT 1;
		
CASE
	WHEN _analog_code IS NOT NULL AND _doc_type=1 THEN
		IF _status_severity='ok' AND _analog_priority THEN
			SET _status_severity='wrn';
		END IF;
		RETURN CONCAT(_status_severity,"_analog"," Найден ",IF(_analog_priority,"приоритетный ",""),"аналог: ",_analog_code," ",_analog_name,"; ",_status_msg);
	WHEN _analog_code AND _doc_type=2 THEN
		IF _status_severity='ok' THEN
			SET _status_severity='wrn';
		END IF;
		RETURN CONCAT(_status_severity,"_analog"," В наличии ",IF(_analog_priority,"приоритетный ",""),"аналог: ",_analog_quantity," ",_analog_code," ",_analog_name,"; ",_status_msg);
	ELSE
		RETURN _row_status;
END CASE;

END$$
DELIMITER ;


DROP function IF EXISTS `CHK_ENTRY`;

DELIMITER $$

CREATE DEFINER=`root`@`localhost` FUNCTION `CHK_ENTRY`( _entry_id INT ) RETURNS varchar(100) CHARSET utf8
    READS SQL DATA
BEGIN
DECLARE _product_code VARCHAR(100) CHARSET utf8;
DECLARE _entry_count INT;
DECLARE _stock_count FLOAT;
DECLARE _reserved FLOAT;
DECLARE _awaiting FLOAT;
DECLARE _doc_id INT;
DECLARE _doc_status_id INT;
DECLARE _doc_type INT;
DECLARE _doc_vat_rate DOUBLE;
DECLARE _is_commited INT;
DECLARE _notcount INT;
DECLARE _invoice_price DOUBLE;
DECLARE _self_price DOUBLE;
DECLARE _breakeven_price DOUBLE;
DECLARE _bpack INT;
DECLARE _spack INT;
DECLARE _weight DOUBLE;
DECLARE _volume DOUBLE;
DECLARE _unit VARCHAR(10) CHARSET utf8;


SELECT doc_id,product_code,product_quantity,invoice_price,self_price,breakeven_price INTO _doc_id,_product_code,_entry_count,_invoice_price,_self_price,_breakeven_price FROM document_entries WHERE doc_entry_id=_entry_id;
SELECT is_commited,doc_type,notcount,doc_status_id,(vat_rate/100+1) INTO _is_commited,_doc_type,_notcount,_doc_status_id,_doc_vat_rate FROM document_list WHERE doc_id=_doc_id;
SELECT product_quantity,product_awaiting,product_reserved INTO _stock_count,_awaiting,_reserved FROM stock_entries WHERE product_code=_product_code;

IF _entry_count=0 THEN RETURN 'err Нулевое колличество'; END IF;
IF _invoice_price=0 THEN RETURN 'err Нулевая цена'; END IF;
IF _self_price=0 AND _is_commited=1 THEN RETURN 'wrn Нулевая себестоимость'; END IF;
IF _stock_count IS NULL AND _doc_type<3 THEN RETURN 'err Отсутствует в реестре склада'; END IF;

SELECT product_bpack,product_spack,product_weight,product_volume,product_unit INTO _bpack,_spack,_weight,_volume,_unit 
    FROM prod_list WHERE product_code=_product_code;

IF _doc_type=1 THEN  
	IF _doc_status_id IS NULL OR _doc_status_id<>2 THEN
		IF _entry_count>_stock_count AND _is_commited=0 THEN RETURN CONCAT(IF(_notcount,'wrn','err'),' На складе нехватает ',(_entry_count-_stock_count),_unit); END IF;
		IF _entry_count>_stock_count-_reserved AND _is_commited=0 THEN RETURN CONCAT(IF(_notcount,'wrn_reserve','err_reserve'),' На складе нехватает с учетом резерва ',(_entry_count-_stock_count+_reserved),_unit); END IF;
	END IF;
    IF ROUND(_invoice_price*_doc_vat_rate,2)<ROUND(_breakeven_price,2) THEN RETURN CONCAT('err_breakeven Цена ниже порога рентабельности ',ROUND(_breakeven_price,2)); END IF;
    IF CEIL(_entry_count/_spack)<>_entry_count/_spack THEN RETURN CONCAT('info Колличество не кратно упаковке ',CEIL(_entry_count/_spack)*_spack,_unit); END IF;
ELSE
    IF CEIL(_entry_count/_bpack)<>_entry_count/_bpack THEN RETURN CONCAT('info Колличество не кратно упаковке ',CEIL(_entry_count/_bpack)*_bpack,_unit); END IF;
END IF;

IF _weight=0 OR _volume=0 THEN RETURN 'info Вес или объем не установлен'; END IF;

RETURN 'ok Ошибок нет';
END$$

DELIMITER ;


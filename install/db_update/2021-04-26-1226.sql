DROP function IF EXISTS `CHK_ENTRY`;

DELIMITER $$
USE `isell_db`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `CHK_ENTRY`( _entry_id INT ) RETURNS varchar(100) CHARSET utf8
    READS SQL DATA
BEGIN
DECLARE _product_code VARCHAR(100) CHARSET utf8;
DECLARE _entry_count FLOAT;
DECLARE _stock_count FLOAT;
DECLARE _reserved FLOAT;
DECLARE _awaiting FLOAT;
DECLARE _doc_id INT;
DECLARE _doc_status_id INT;
DECLARE _doc_type INT;
DECLARE _doc_vat_rate DOUBLE;
DECLARE _is_commited INT;
DECLARE _notcount INT;
DECLARE _is_reclamation INT;
DECLARE _invoice_price DOUBLE;
DECLARE _self_price DOUBLE;
DECLARE _breakeven_price DOUBLE;
DECLARE _bpack INT;
DECLARE _spack INT;
DECLARE _weight DOUBLE;
DECLARE _volume DOUBLE;
DECLARE _unit VARCHAR(10) CHARSET utf8;
DECLARE _awaiting_filtered_qty FLOAT;

SELECT doc_id,product_code,product_quantity,invoice_price,self_price,breakeven_price INTO _doc_id,_product_code,_entry_count,_invoice_price,_self_price,_breakeven_price FROM document_entries WHERE doc_entry_id=_entry_id;
SELECT is_commited,doc_type,notcount,is_reclamation,doc_status_id,(vat_rate/100+1) INTO _is_commited,_doc_type,_notcount,_is_reclamation,_doc_status_id,_doc_vat_rate FROM document_list WHERE doc_id=_doc_id;
SELECT product_quantity,product_awaiting,product_reserved INTO _stock_count,_awaiting,_reserved FROM stock_entries WHERE product_code=_product_code;

IF _entry_count=0 THEN RETURN 'err Нулевое колличество'; END IF;
IF _invoice_price=0 THEN RETURN 'err Нулевая цена'; END IF;
IF _self_price=0 AND _is_commited=1 THEN RETURN 'wrn Нулевая себестоимость'; END IF;
IF _stock_count IS NULL AND _doc_type<3 THEN RETURN 'err Отсутствует в реестре склада'; END IF;

SELECT product_bpack,product_spack,product_weight,product_volume,product_unit INTO _bpack,_spack,_weight,_volume,_unit 
    FROM prod_list WHERE product_code=_product_code;

IF _doc_type=1 AND NOT _is_reclamation THEN  
	IF _doc_status_id IS NULL OR _doc_status_id<>2 THEN
		IF _entry_count>_stock_count AND _is_commited=0 AND _entry_count<=_stock_count+_awaiting THEN 
        
			SELECT SUM(product_quantity) INTO _awaiting_filtered_qty FROM 
				document_entries rde
					JOIN 
				document_list rdl USING(doc_id)
			WHERE rde.product_code=_product_code AND doc_type=2 AND NOT is_reclamation AND NOT notcount AND rdl.doc_status_id=2 AND DATE_ADD(NOW(), INTERVAL 3 DAY)>rdl.cstamp;
            IF _entry_count<=_stock_count+_awaiting_filtered_qty THEN
				RETURN CONCAT(IF(_notcount,'wrn_awaiting','err_awaiting'),' В наличии:',_stock_count,' Ожидается:',_awaiting_filtered_qty);
			END IF;
            
		END IF;
		IF _entry_count>_stock_count AND _is_commited=0 THEN RETURN CONCAT(IF(_notcount,'wrn','err'),' На складе нехватает ',(_entry_count-_stock_count),_unit); END IF;
		IF _entry_count>_stock_count-_reserved AND _is_commited=0 THEN RETURN CONCAT(IF(_notcount,'wrn_reserve','err_reserve'),' На складе нехватает с учетом резерва ',(_entry_count-_stock_count+_reserved),_unit); END IF;
	END IF;
    IF ROUND(_invoice_price*_doc_vat_rate,2)<ROUND(_breakeven_price,2) THEN RETURN CONCAT('err_breakeven Цена ниже порога рентабельности ',ROUND(_breakeven_price,2)); END IF;
    IF CEIL(_entry_count/_spack)<>_entry_count/_spack THEN RETURN CONCAT('ok Колличество не кратно упаковке ',CEIL(_entry_count/_spack)*_spack,_unit); END IF;
ELSE
    IF CEIL(_entry_count/_bpack)<>_entry_count/_bpack THEN RETURN CONCAT('ok Колличество не кратно упаковке ',CEIL(_entry_count/_bpack)*_bpack,_unit); END IF;
END IF;

IF _weight=0 OR _volume=0 THEN RETURN 'ok Вес или объем не установлен'; END IF;

RETURN 'ok Ошибок нет';
END$$

DELIMITER ;



DROP function IF EXISTS `GET_BREAKEVEN_PRICE`;

DELIMITER $$
USE `isell_db`$$
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

USE `isell_db`;
DROP function IF EXISTS `GET_PARTLY_PAYED`;

DELIMITER $$
USE `isell_db`$$
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

USE `isell_db`;
DROP function IF EXISTS `LEFTOVER_CALC`;

DELIMITER $$
USE `isell_db`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `LEFTOVER_CALC`(_product_code VARCHAR(45),_fdate DATETIME,_quantity INT,_return VARCHAR(45)) RETURNS varchar(45) CHARSET utf8
    READS SQL DATA
    DETERMINISTIC
BEGIN

DECLARE _a INT;
DECLARE _b INT;
SET 
     @party_quantity:=0,

	 @leftover_to_date:=0,
     @leftover_total_sum:=0,
     @leftover_total_qty:=0,
     
     @sold_total_qty:=_quantity,
     @sold_total_self:=0,
     @sold_first_plabel:='',
     @sold_counter:=@sold_total_qty,
     
     @include_vat:=IF(_return LIKE '%include_vat%',1,0);

SELECT *
INTO _a,_b
FROM(SELECT 
		@sold_total_self:=@sold_total_self+IF( @sold_counter >0,LEAST( @sold_counter, party_quantity) * self_price,0) sold_total_self,
		@sold_counter:=@sold_counter-party_quantity sc
	FROM
		(SELECT 
			*,
			@leftover_to_date,
			@party_quantity:=LEAST(@leftover_to_date - @leftover_total_qty,product_quantity) party_quantity,
			@leftover_total_sum:=@leftover_total_sum+@party_quantity*self_price,
			@leftover_total_qty:=@leftover_total_qty + product_quantity tb,
			IF(@sold_first_plabel<>'', @sold_first_plabel:=party_label, '') first_party_label
		FROM
			(SELECT
				doc_type,
				cstamp,
				party_label,
				self_price*IF(@include_vat,dl.vat_rate/100+1,1) self_price,
				product_quantity,
				@leftover_to_date:=@leftover_to_date+IF(doc_type=2,product_quantity,-product_quantity) lo
			FROM
				document_entries
					JOIN
				document_list dl USING(doc_id)
			WHERE
				(doc_type=1 OR doc_type=2)
				AND notcount=0
				AND is_commited=1
				AND cstamp<_fdate
				AND product_code=_product_code
			ORDER BY cstamp ASC) t
		WHERE 
			doc_type=2
		HAVING 
			party_quantity>0
		ORDER BY cstamp DESC) t2
	ORDER BY cstamp ASC) t3
LIMIT 1;
		
CASE 
	WHEN _return LIKE '%first_party%' THEN 
		RETURN @sold_first_plabel;
    WHEN _return LIKE '%selfprice%' THEN  
		RETURN COALESCE(@sold_total_self/LEAST(@sold_total_qty,@leftover_to_date),@leftover_total_sum/@leftover_to_date);
	WHEN _return LIKE '%all%' THEN
		DROP TEMPORARY TABLE IF EXISTS tmp_leftover_calculated;
		CREATE TEMPORARY TABLE tmp_leftover_calculated AS (SELECT @sold_first_plabel party_label,COALESCE(@sold_total_self/@sold_total_qty,@leftover_total_sum/@leftover_to_date) self_price,@leftover_to_date leftover);
END CASE;

RETURN @leftover_to_date;
END$$

DELIMITER ;


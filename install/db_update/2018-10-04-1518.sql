DROP function IF EXISTS `LEFTOVER_CALC`;
DELIMITER $$
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
		RETURN COALESCE(@sold_total_self/@sold_total_qty,@leftover_total_sum/@leftover_to_date);
	WHEN _return LIKE '%all%' THEN
		DROP TEMPORARY TABLE IF EXISTS tmp_leftover_calculated;
		CREATE TEMPORARY TABLE tmp_leftover_calculated AS (SELECT @sold_first_plabel party_label,COALESCE(@sold_total_self/@sold_total_qty,@leftover_total_sum/@leftover_to_date) self_price,@leftover_to_date leftover);
END CASE;

RETURN @leftover_to_date;
END$$
DELIMITER ;

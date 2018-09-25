DROP procedure IF EXISTS `SELF_PRICE_CALC`;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `SELF_PRICE_CALC`(IN _product_code VARCHAR(45),IN _fdate DATETIME,IN _quantity INT,OUT _avg_self DOUBLE,OUT _leftover DOUBLE,OUT _first_party_label VARCHAR(45))
BEGIN
SET 
     @party_quantity:=0,

	 @leftover_to_date:=0,
     @leftover_total_sum:=0,
     @leftover_total_qty:=0,
     
     @sold_total_qty:=_quantity,
     @sold_total_self:=0,
     @sold_first_plabel:='',
     @sold_counter:=@sold_total_qty;

SELECT 
	cstamp,
    party_label,
    self_price,
    party_quantity,
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
			self_price,
			product_quantity,
			@leftover_to_date:=@leftover_to_date+IF(doc_type=2,product_quantity,-product_quantity) lo
		FROM
			document_entries
				JOIN
			document_list USING(doc_id)
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
ORDER BY cstamp ASC;
		
	SELECT @sold_first_plabel,COALESCE(@sold_total_self/@sold_total_qty,@leftover_total_sum/@leftover_to_date),@leftover_to_date INTO _first_party_label, _avg_self, _leftover;
END$$

DELIMITER ;


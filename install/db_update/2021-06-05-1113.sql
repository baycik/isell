CREATE  OR REPLACE VIEW `product_popularity_list` AS

SELECT
	path,
    product_code,
    ru product_name,
    product_quantity,
    DATE_FORMAT(fetch_stamp,'%d.%m.%Y') last_fetched
FROM
		stock_entries se
    JOIN
		stock_tree st ON se.parent_id=st.branch_id
	JOIN
		prod_list USING(product_code)
ORDER BY 
	(fetch_count-IF(fetch_stamp IS NULL,0,DATEDIFF(NOW(),fetch_stamp)));

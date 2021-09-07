CREATE VIEW `stock_checked_items` AS
SELECT
	product_code,
    ru product_name,
	DATE_FORMAT(cstamp,'%d.%m.%Y') date_dot,
    se.product_quantity,
    product_sector
FROM
		stock_entries se
    JOIN
		prod_list pl USING(product_code)
    LEFT JOIN
		(SELECT 
			product_id,
			MAX(cstamp) cstamp
		FROM
			checkout_entries ce
				JOIN
			checkout_list cl ON ce.checkout_id = cl.checkout_id
		WHERE
				parent_doc_id IS NULL
				AND cl.checkout_status IN ('checked' , 'checked_with_divergence')
		GROUP BY product_id) checkout USING(product_id)
WHERE
	se.product_quantity>0
GROUP BY
	product_id
ORDER BY COALESCE(checkout.cstamp,0),se.fetch_count,product_code
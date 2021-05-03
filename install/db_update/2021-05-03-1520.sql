CREATE  OR REPLACE VIEW `discount_list` AS
SELECT 
    company_name,
    st.label,
    ROUND((100-discount*100),2) disc
FROM
    companies_discounts cd
        JOIN
    companies_list USING (company_id)
        JOIN
    stock_tree st ON st.branch_id = cd.branch_id;
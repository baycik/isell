/**
 * Author:  admin
 * Created: Nov 9, 2017
 */

CREATE  OR REPLACE VIEW `client_list` AS
SELECT 
    label,
    company_name,
    path,
    company_person,
    company_mobile,
    company_email,
    company_web,
    company_address,
    company_jaddress,
    company_director,
    company_description
FROM
    companies_list
        JOIN
    companies_tree USING (branch_id);
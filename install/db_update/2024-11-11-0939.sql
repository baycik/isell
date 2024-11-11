ALTER TABLE `isell_db`.`document_list` 
ADD INDEX `cstamp` (`cstamp` ASC);
;


ALTER TABLE `isell_db`.`document_list` 
ADD INDEX `docflags` (`doc_type` ASC, `is_commited` ASC, `notcount` ASC, `is_reclamation` ASC);
;


ALTER TABLE `isell_db`.`acc_trans` 
ADD INDEX `cstamp` (`cstamp` ASC);
;


ALTER TABLE `isell_db`.`prod_list` 
ADD INDEX `analyse` (`analyse_type` ASC, `analyse_brand` ASC, `analyse_class` ASC, `analyse_origin` ASC);
;

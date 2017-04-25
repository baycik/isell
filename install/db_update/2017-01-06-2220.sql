/**
 * Author:  Baycik
 * Created: Jan 6, 2017
 */

ALTER TABLE `document_list` 
DROP FOREIGN KEY `fk_document_list_document_types1`;
ALTER TABLE `document_list` 
DROP INDEX `fk_document_list_document_types1_idx` ;

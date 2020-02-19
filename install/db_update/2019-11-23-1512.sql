ALTER TABLE `document_view_list` 
CHANGE COLUMN `view_efield_values` `view_efield_values` TEXT NULL DEFAULT NULL ;
update document_view_list set view_efield_values=null where view_efield_values='';
ALTER TABLE `document_view_list` 
CHANGE COLUMN `view_efield_values` `view_efield_values` JSON NULL DEFAULT NULL ;
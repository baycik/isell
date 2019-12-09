UPDATE `document_view_types` SET `view_tpl`='ru/doc/act.xlsx' WHERE `view_type_id`='137';
DELETE FROM `document_view_list` WHERE `view_type_id`='138' OR `view_type_id`='139';
DELETE FROM `document_view_types` WHERE `view_type_id`='138' OR `view_type_id`='139';

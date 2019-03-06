CREATE TABLE `document_status_list` (
  `doc_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_code` varchar(45) DEFAULT NULL,
  `status_description` varchar(45) DEFAULT NULL,
  `user_level` tinyint(4) DEFAULT NULL,
  `commited_only` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`doc_status_id`)
) ENGINE=InnoDBDEFAULT CHARSET=utf8;






INSERT INTO `document_status_list` (`doc_status_id`,`status_code`,`status_description`,`user_level`,`commited_only`) VALUES (1,'created','Выписан',1,0);
INSERT INTO `document_status_list` (`doc_status_id`,`status_code`,`status_description`,`user_level`,`commited_only`) VALUES (2,'reserved','В резерве',2,0);
INSERT INTO `document_status_list` (`doc_status_id`,`status_code`,`status_description`,`user_level`,`commited_only`) VALUES (3,'processed','Обработан складом',2,1);


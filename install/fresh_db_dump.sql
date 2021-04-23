-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: isell_db
-- ------------------------------------------------------
-- Server version	5.7.11

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acc_article_list`
--

DROP TABLE IF EXISTS `acc_article_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_article_list` (
  `article_id` int(11) NOT NULL,
  `article_name` varchar(45) DEFAULT NULL,
  `article_group` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_article_list`
--

LOCK TABLES `acc_article_list` WRITE;
/*!40000 ALTER TABLE `acc_article_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `acc_article_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acc_check_list`
--

DROP TABLE IF EXISTS `acc_check_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_check_list` (
  `check_id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` int(11) DEFAULT NULL,
  `active_company_id` int(10) unsigned NOT NULL,
  `main_acc_code` varchar(15) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `value_date` datetime DEFAULT NULL,
  `assumption_date` datetime DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `debit_amount` double DEFAULT NULL,
  `credit_amount` double DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `assignment` varchar(255) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_code` bigint(20) DEFAULT NULL,
  `client_code1` varchar(45) DEFAULT NULL,
  `client_account` varchar(45) DEFAULT NULL,
  `client_corr_account` varchar(45) DEFAULT NULL,
  `client_bank_name` varchar(255) DEFAULT NULL,
  `client_bank_code` int(11) DEFAULT NULL,
  `correspondent_name` varchar(255) DEFAULT NULL,
  `correspondent_code` bigint(20) DEFAULT NULL,
  `correspondent_code1` varchar(45) DEFAULT NULL,
  `correspondent_account` varchar(45) DEFAULT NULL,
  `correspondent_corr_account` varchar(45) DEFAULT NULL,
  `correspondent_bank_name` varchar(255) DEFAULT NULL,
  `correspondent_bank_code` int(11) DEFAULT NULL,
  `creator_status` varchar(45) DEFAULT NULL,
  `payment_type` varchar(45) DEFAULT NULL,
  `payment_type1` varchar(45) DEFAULT NULL,
  `payment_time` varchar(45) DEFAULT NULL,
  `payment_queue` varchar(45) DEFAULT NULL,
  `index_kbk` varchar(45) DEFAULT NULL,
  `index_okato` varchar(45) DEFAULT NULL,
  `index_reason` varchar(45) DEFAULT NULL,
  `index_number` varchar(45) DEFAULT NULL,
  `index_date` varchar(45) DEFAULT NULL,
  `index_type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`check_id`),
  UNIQUE KEY `unq` (`number`,`transaction_date`,`correspondent_code`,`debit_amount`,`credit_amount`,`active_company_id`,`assignment`),
  KEY `fk_acc_check_list_acc_tree1_idx` (`main_acc_code`),
  KEY `fk_acc_check_list_companies_list1_idx` (`active_company_id`),
  CONSTRAINT `fk_acc_check_list_acc_tree1` FOREIGN KEY (`main_acc_code`) REFERENCES `acc_tree` (`acc_code`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_acc_check_list_companies_list1` FOREIGN KEY (`active_company_id`) REFERENCES `companies_list` (`company_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_check_list`
--

LOCK TABLES `acc_check_list` WRITE;
/*!40000 ALTER TABLE `acc_check_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `acc_check_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acc_trans`
--

DROP TABLE IF EXISTS `acc_trans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_trans` (
  `trans_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trans_ref` int(10) DEFAULT NULL,
  `trans_article` varchar(45) DEFAULT NULL,
  `trans_status` tinyint(1) NOT NULL,
  `trans_role` varchar(20) DEFAULT NULL,
  `doc_id` int(11) DEFAULT NULL,
  `check_id` int(11) DEFAULT NULL,
  `is_disabled` tinyint(1) DEFAULT NULL,
  `editable` tinyint(1) NOT NULL DEFAULT '0',
  `active_company_id` int(10) unsigned NOT NULL,
  `passive_company_id` int(10) unsigned NOT NULL,
  `acc_debit_code` varchar(15) NOT NULL,
  `acc_credit_code` varchar(15) NOT NULL,
  `amount` double NOT NULL,
  `amount_alt` double NOT NULL,
  `description` varchar(255) NOT NULL,
  `cstamp` datetime DEFAULT NULL,
  `tstamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`trans_id`),
  KEY `FK_acc_active_comp` (`active_company_id`) USING BTREE,
  KEY `FK_acc_passive_comp` (`passive_company_id`) USING BTREE,
  KEY `acc_credit_code_idx` (`acc_credit_code`),
  KEY `acc_debit_code_idx` (`acc_debit_code`),
  KEY `fk_acc_trans_user_list1_idx` (`modified_by`),
  KEY `fk_acc_trans_user_list2_idx` (`created_by`),
  KEY `doc_id` (`doc_id`),
  CONSTRAINT `FK_acc_activecid` FOREIGN KEY (`active_company_id`) REFERENCES `companies_list` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_acc_passivecid` FOREIGN KEY (`passive_company_id`) REFERENCES `companies_list` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `acc_credit_code` FOREIGN KEY (`acc_credit_code`) REFERENCES `acc_tree` (`acc_code`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `acc_debit_code` FOREIGN KEY (`acc_debit_code`) REFERENCES `acc_tree` (`acc_code`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_acc_trans_user_list1` FOREIGN KEY (`modified_by`) REFERENCES `user_list` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_acc_trans_user_list2` FOREIGN KEY (`created_by`) REFERENCES `user_list` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_trans`
--

LOCK TABLES `acc_trans` WRITE;
/*!40000 ALTER TABLE `acc_trans` DISABLE KEYS */;
/*!40000 ALTER TABLE `acc_trans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acc_trans_names`
--

DROP TABLE IF EXISTS `acc_trans_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_trans_names` (
  `acc_debit_code` varchar(15) NOT NULL,
  `acc_credit_code` varchar(15) NOT NULL,
  `trans_name` varchar(45) NOT NULL,
  `user_level` tinyint(4) NOT NULL,
  `trans_label` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`acc_debit_code`,`acc_credit_code`),
  KEY `fk_acc_trans_names_acc_tree2_idx` (`acc_credit_code`),
  CONSTRAINT `fk_acc_trans_names_acc_tree1` FOREIGN KEY (`acc_debit_code`) REFERENCES `acc_tree` (`acc_code`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_acc_trans_names_acc_tree2` FOREIGN KEY (`acc_credit_code`) REFERENCES `acc_tree` (`acc_code`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_trans_names`
--

LOCK TABLES `acc_trans_names` WRITE;
/*!40000 ALTER TABLE `acc_trans_names` DISABLE KEYS */;
INSERT INTO `acc_trans_names` VALUES ('301','361','Оплата Наличными',2,NULL),('311','361',' Оплата на расчетный счет',2,NULL),('311','37','Фин. Помощь Получение',3,NULL),('312','333','Валюта покупка',2,NULL),('333','311','Валюта перечисление на покупку',2,NULL),('37','311','Фин. Помощь Выдача',3,NULL),('631','301','Выплата Наличными',2,NULL),('631','311','Выплата Расчетный счет',2,NULL);
/*!40000 ALTER TABLE `acc_trans_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acc_trans_status`
--

DROP TABLE IF EXISTS `acc_trans_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_trans_status` (
  `trans_status` tinyint(1) NOT NULL,
  `code` varchar(45) DEFAULT NULL,
  `command` varchar(45) DEFAULT NULL,
  `descr` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`trans_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_trans_status`
--

LOCK TABLES `acc_trans_status` WRITE;
/*!40000 ALTER TABLE `acc_trans_status` DISABLE KEYS */;
INSERT INTO `acc_trans_status` VALUES (0,'unknown',NULL,''),(1,'unpayed',NULL,'Неоплачено'),(2,'partly',NULL,'Частично оплачено'),(3,'payed',NULL,'Оплачено'),(4,'closed',NULL,'Оплачено'),(5,'closing',NULL,''),(6,'unpayedsq',NULL,'Невыплачено'),(7,'partlysq',NULL,'Частично выплачено'),(8,'payedsq',NULL,'Выплачено'),(9,'closedsq',NULL,'Выплачено'),(10,'closingsq',NULL,NULL);
/*!40000 ALTER TABLE `acc_trans_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acc_tree`
--

DROP TABLE IF EXISTS `acc_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_tree` (
  `branch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `label` varchar(45) NOT NULL,
  `is_leaf` tinyint(1) NOT NULL,
  `branch_data` text NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `path` text NOT NULL,
  `acc_code` varchar(15) DEFAULT NULL,
  `acc_type` varchar(2) DEFAULT NULL,
  `acc_role` varchar(45) DEFAULT NULL,
  `curr_id` int(10) unsigned DEFAULT NULL,
  `is_favorite` tinyint(1) DEFAULT NULL,
  `use_clientbank` tinyint(1) DEFAULT NULL,
  `top_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `acc_code_UNIQUE` (`acc_code`)
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_tree`
--

LOCK TABLES `acc_tree` WRITE;
/*!40000 ALTER TABLE `acc_tree` DISABLE KEYS */;
INSERT INTO `acc_tree` VALUES (1,27,'Товары на складе',1,'{\"im0\":\"coins.png\"}',0,'/Запасы/Товары/Товары на складе/','281','A',NULL,1,NULL,NULL,NULL),(3,163,'Текущий счет',1,'{\"im0\":\"coins.png\"}',0,'/Денежные средства, расчeты и прoчие активы/Счета в банках/Текущий счет/','311','A',NULL,1,1,1,NULL),(5,169,'Расчеты c покупателями',1,'{\"im0\":\"coins.png\"}',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с покупателями, заказчиками/Расчеты c покупателями/','361','P',NULL,NULL,1,NULL,NULL),(6,29,'Нераспределенная прибыль',1,'{\"im0\":\"coins.png\"}',0,'/Собствeнный капитал и обеспeчeние обязательст/44 Прибыль/Нераспределенная прибыль/','441','P',NULL,NULL,NULL,NULL,NULL),(7,265,'Результат операционной деятельности ',1,'{\"im0\":\"coins.png\"}',0,'/Доходы и результаты деятельнocти/Финансовые результаты/Результат операционной деятельности /','791','P',NULL,NULL,NULL,NULL,NULL),(11,234,'Доход от реализации товаров',1,'{\"im0\":\"coins.png\"}',0,'/Доходы и результаты деятельнocти/Доходы от реализации/Доход от реализации товаров/','702','P',NULL,NULL,NULL,NULL,NULL),(12,0,'Необоротные активы',0,'',0,'/Необоротные активы/','1',NULL,NULL,NULL,NULL,NULL,NULL),(13,0,'Запасы',0,'',0,'/Запасы/','2',NULL,NULL,NULL,NULL,NULL,NULL),(14,212,'Расчеты по налогам НДС',1,'{\"im0\":\"coins.png\"}',0,'/Текущие обязательства/Расчеты по налогам и плaтежам/Расчеты по налогам НДС/','641','P',NULL,NULL,NULL,NULL,NULL),(16,210,'Расчеты с поставщиками',1,'{\"im0\":\"coins.png\"}',0,'/Текущие обязательства/Расчеты с поставщиками, подрядчиками/Расчеты с поставщиками/','631','P',NULL,NULL,NULL,NULL,NULL),(22,216,'Единый социальный взнос',1,'{\"im0\":\"coins.png\"}',0,'/Текущие обязательства/Расчеты по страхованию/Единый социальный взнос/','652','P',NULL,NULL,NULL,NULL,NULL),(24,220,'Расчеты по заработной плате',1,'{\"im0\":\"coins.png\"}',0,'/Текущие обязательства/Расчеты по выплaтам работникам/Расчеты по заработной плате/','661','P',NULL,NULL,NULL,NULL,NULL),(25,142,'Административные расходы',0,'{\"im0\":\"coins.png\"}',0,'/Затраты деятельности/Административные расходы/','92','P',NULL,NULL,NULL,NULL,NULL),(26,145,'Налог на прибыль от oбычной деятельности',1,'{\"im0\":\"coins.png\"}',0,'/Затраты деятельности/Налог на прибыль/Налог на прибыль от oбычной деятельности/','981','P',NULL,NULL,NULL,NULL,NULL),(27,13,'Товары',0,'{\"im0\":\"coins.png\"}',0,'/Запасы/Товары/','28','A',NULL,NULL,NULL,NULL,NULL),(29,137,'44 Прибыль',0,'{\"im0\":\"coins.png\"}',0,'/Собствeнный капитал и обеспeчeние обязательст/44 Прибыль/','44','P',NULL,NULL,NULL,NULL,NULL),(31,0,'Денежные средства, расчeты и прoчие активы',0,'',0,'/Денежные средства, расчeты и прoчие активы/','3','A',NULL,NULL,0,0,NULL),(40,129,'Непредвиденные активы',1,'{\"im0\":\"coins.png\"}',0,'/Забалансовые счета/Непредвиденные активы/','04','P',NULL,NULL,NULL,NULL,NULL),(53,171,'Расчеты с подотчеными лицами',1,'{\"im0\":\"coins.png\"}',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/Расчеты с подотчеными лицами/','372','AP',NULL,NULL,NULL,NULL,NULL),(54,171,'Расчеты c прочими дебиторами',1,'{\"im0\":\"coins.png\"}',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/Расчеты c прочими дебиторами/','377','AP',NULL,NULL,NULL,NULL,NULL),(55,129,'Списанные активы',1,'{\"im0\":\"coins.png\"}',0,'/Забалансовые счета/Списанные активы/','07','P',NULL,NULL,NULL,NULL,NULL),(56,160,'Наличность в национальной валюте',1,'{\"im0\":\"coins.png\"}',0,'/Денежные средства, расчeты и прoчие активы/Наличность/Наличность в национальной валюте/','301','A',NULL,NULL,NULL,NULL,NULL),(57,137,'Уставной капитал',1,'{\"im0\":\"coins.png\"}',0,'/Собствeнный капитал и обеспeчeние обязательст/Уставной капитал/','40','P',NULL,NULL,NULL,NULL,NULL),(58,163,'Прoчие счета',0,'{\"im0\":\"coins.png\"}',0,'/Денежные средства, расчeты и прoчие активы/Счета в банках/Прoчие счета/','313','A',NULL,NULL,NULL,NULL,NULL),(60,67,'Машины и оборудование',1,'{\"im0\":\"coins.png\"}',0,'/Необоротные активы/Основные средства/Машины и оборудование/','104','A',NULL,NULL,NULL,NULL,NULL),(61,98,'Износ прочих активов',1,'{\"im0\":\"coins.png\"}',0,'/Необоротные активы/Амортизация неoборотных активов/Износ прочих активов/','132','A',NULL,NULL,NULL,NULL,NULL),(62,212,'Расчеты по налогам НДФЛ',1,'{\"im0\":\"coins.png\"}',0,'/Текущие обязательства/Расчеты по налогам и плaтежам/Расчеты по налогам НДФЛ/','6411','P',NULL,NULL,NULL,NULL,NULL),(64,163,'Текущие счета в иностраннoй валюте',1,'{\"im0\":\"coins.png\"}',0,'/Денежные средства, расчeты и прoчие активы/Счета в банках/Текущие счета в иностраннoй валюте/','312','A',NULL,2,NULL,NULL,NULL),(65,31,'Пpoчие денежные средства',0,'',0,'/Денежные средства, расчeты и прoчие активы/Пpoчие денежные средства/','33',NULL,NULL,NULL,NULL,NULL,NULL),(66,65,'Денежные средства в пyти',1,'{\"im0\":\"coins.png\"}',0,'/ Акт/Валютные счета/Денежные средства в пyти/','333','A',NULL,NULL,NULL,NULL,NULL),(67,12,'Основные средства',0,'',0,'/Необоротные активы/Основные средства/','10',NULL,NULL,NULL,NULL,NULL,NULL),(68,67,'Инвестиционная недвижимость',1,'',0,'/Необоротные активы/Основные средства/Инвестиционная недвижимость/','100',NULL,NULL,NULL,NULL,NULL,NULL),(69,67,'Земельные участки',1,'',0,'/Необоротные активы/Основные средства/Земельные участки/','101',NULL,NULL,NULL,NULL,NULL,NULL),(70,67,'Капитальные затраты нa улучшение земель',1,'',0,'/Необоротные активы/Основные средства/Капитальные затраты нa улучшение земель/','102',NULL,NULL,NULL,NULL,NULL,NULL),(71,67,'Здания и сооружения',1,'',0,'/Необоротные активы/Основные средства/Здания и сооружения/','103',NULL,NULL,NULL,NULL,NULL,NULL),(79,67,'Инструменты, приспособления, инвентарь',1,'',0,'/Необоротные активы/Основные средства/Инструменты, приспособления, инвентарь/','105',NULL,NULL,NULL,NULL,NULL,NULL),(80,67,'Животные',1,'',0,'/Необоротные активы/Основные средства/Животные/','106',NULL,NULL,NULL,NULL,NULL,NULL),(81,67,' Многолетние насаждения',1,'',0,'/Необоротные активы/Основные средства/ Многолетние насаждения/','107',NULL,NULL,NULL,NULL,NULL,NULL),(82,67,'Другие основные средства',1,'',0,'/Необоротные активы/Основные средства/Другие основные средства/','108',NULL,NULL,NULL,NULL,NULL,NULL),(83,12,'Прочиe необоротные материальные активы',0,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/','11',NULL,NULL,NULL,NULL,NULL,NULL),(84,83,'Библиотечные фонды',1,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/Библиотечные фонды/','111',NULL,NULL,NULL,NULL,NULL,NULL),(85,83,'Малоценные необоротные материальные активы',1,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/Малоценные необоротные материальные активы/','112',NULL,NULL,NULL,NULL,NULL,NULL),(86,83,'Временные &#40;нетитульные&#41; сооружения',1,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/Временные &#40;нетитульные&#41; сооружения/','113',NULL,NULL,NULL,NULL,NULL,NULL),(87,83,'Природные ресурсы',1,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/Природные ресурсы/','114',NULL,NULL,NULL,NULL,NULL,NULL),(88,83,'Инвентарная тара',1,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/Инвентарная тара/','115',NULL,NULL,NULL,NULL,NULL,NULL),(89,83,'Предметы проката',1,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/Предметы проката/','116',NULL,NULL,NULL,NULL,NULL,NULL),(90,83,'Другие необоротные материальные активы',1,'',0,'/Необоротные активы/Прочиe необоротные материальные активы/Другие необоротные материальные активы/','117',NULL,NULL,NULL,NULL,NULL,NULL),(91,12,'Нематериальные активы',0,'',0,'/Необоротные активы/Нематериальные активы/','12',NULL,NULL,NULL,NULL,NULL,NULL),(92,91,'Права пользования природными ресурсами',1,'',0,'/Необоротные активы/Нематериальные активы/Права пользования природными ресурсами/','121',NULL,NULL,NULL,NULL,NULL,NULL),(93,91,'Права пользования имуществом',1,'',0,'/Необоротные активы/Нематериальные активы/Права пользования имуществом/','122',NULL,NULL,NULL,NULL,NULL,NULL),(94,91,'Права нa товарные знаки',1,'',0,'/Необоротные активы/Нематериальные активы/Права нa товарные знаки/','123',NULL,NULL,NULL,NULL,NULL,NULL),(95,91,'Права нa объекты промышленной собственности',1,'',0,'/Необоротные активы/Нематериальные активы/Права нa объекты промышленной собственности/','124',NULL,NULL,NULL,NULL,NULL,NULL),(96,91,'Авторское право и cмежные c ним права',1,'',0,'/Необоротные активы/Нематериальные активы/Авторское право и cмежные c ним права/','125',NULL,NULL,NULL,NULL,NULL,NULL),(97,91,'Пpочие нематериальные активы',1,'',0,'/Необоротные активы/Нематериальные активы/Пpочие нематериальные активы/','126',NULL,NULL,NULL,NULL,NULL,NULL),(98,12,'Амортизация неoборотных активов',0,'',0,'/Необоротные активы/Амортизация неoборотных активов/','13',NULL,NULL,NULL,NULL,NULL,NULL),(99,98,'Износ основных средств',1,'',0,'/Необоротные активы/Амортизация неoборотных активов/Износ основных средств/','131',NULL,NULL,NULL,NULL,NULL,NULL),(103,98,'Накопленная амортизация долгосрочныx биологич',1,'',0,'/Необоротные активы/Амортизация неoборотных активов/Накопленная амортизация долгосрочныx биологич/','134',NULL,NULL,NULL,NULL,NULL,NULL),(104,98,'Износ инвестиционной недвижимости',1,'',0,'/Необоротные активы/Амортизация неoборотных активов/Износ инвестиционной недвижимости/','135',NULL,NULL,NULL,NULL,NULL,NULL),(105,12,'Долгосрочные финансовые инвестиции',0,'',0,'/Необоротные активы/Долгосрочные финансовые инвестиции/','14',NULL,NULL,NULL,NULL,NULL,NULL),(107,105,'Инвестиции связанным сторонам пo методу учета',1,'',0,'/Необоротные активы/Долгосрочные финансовые инвестиции/Инвестиции связанным сторонам пo методу учета/','141',NULL,NULL,NULL,NULL,NULL,NULL),(108,105,'Другие инвестиции связанным сторонам',1,'',0,'/Необоротные активы/Долгосрочные финансовые инвестиции/Другие инвестиции связанным сторонам/','142',NULL,NULL,NULL,NULL,NULL,NULL),(109,105,'Инвестиции несвязанным сторонам',1,'',0,'/Необоротные активы/Долгосрочные финансовые инвестиции/Инвестиции несвязанным сторонам/','143',NULL,NULL,NULL,NULL,NULL,NULL),(110,12,'Капитальные инвестиции',0,'',0,'/Необоротные активы/Капитальные инвестиции/','15',NULL,NULL,NULL,NULL,NULL,NULL),(112,110,'Капитальное строительство',1,'',0,'/Необоротные активы/Капитальные инвестиции/Капитальное строительство/','151',NULL,NULL,NULL,NULL,NULL,NULL),(113,110,'Приобретение &#40;изготовление&#41; основных ',1,'',0,'/Необоротные активы/Капитальные инвестиции/Приобретение &#40;изготовление&#41; основных /','152',NULL,NULL,NULL,NULL,NULL,NULL),(114,110,'Приобретние &#40;изготовление&#41; прочиx нео',1,'',0,'/Необоротные активы/Капитальные инвестиции/Приобретние &#40;изготовление&#41; прочиx нео/','153',NULL,NULL,NULL,NULL,NULL,NULL),(115,110,'Приобретение &#40;создание&#41; нематериальны',1,'',0,'/Необоротные активы/Капитальные инвестиции/Приобретение &#40;создание&#41; нематериальны/','154',NULL,NULL,NULL,NULL,NULL,NULL),(116,110,'Приобретение &#40;выращивание&#41; долгосрочн',1,'',0,'/Необоротные активы/Капитальные инвестиции/Приобретение &#40;выращивание&#41; долгосрочн/','155',NULL,NULL,NULL,NULL,NULL,NULL),(117,12,'Долгосрочные биологические активы',1,'',0,'/Необоротные активы/Долгосрочные биологические активы/','16',NULL,NULL,NULL,NULL,NULL,NULL),(118,12,'Отсроченные налоговые aктивы',1,'',0,'/Необоротные активы/Отсроченные налоговые aктивы/','17',NULL,NULL,NULL,NULL,NULL,NULL),(119,12,'Долгосрочнaя дебиторская задолженность и проч',0,'',0,'/Необоротные активы/Долгосрочнaя дебиторская задолженность и проч/','18',NULL,NULL,NULL,NULL,NULL,NULL),(120,119,'Задолженность за имущество',1,'',0,'/Необоротные активы/Долгосрочнaя дебиторская задолженность и проч/Задолженность за имущество/','181',NULL,NULL,NULL,NULL,NULL,NULL),(121,119,'Долгосрочные векселя полученные',1,'',0,'/Необоротные активы/Долгосрочнaя дебиторская задолженность и проч/Долгосрочные векселя полученные/','182',NULL,NULL,NULL,NULL,NULL,NULL),(122,119,'Прочая дебиторская задолженность',1,'',0,'/Необоротные активы/Долгосрочнaя дебиторская задолженность и проч/Прочая дебиторская задолженность/','183',NULL,NULL,NULL,NULL,NULL,NULL),(123,119,'Прочие необоротные активы',1,'',0,'/Необоротные активы/Долгосрочнaя дебиторская задолженность и проч/Прочие необоротные активы/','184',NULL,NULL,NULL,NULL,NULL,NULL),(124,12,'Гудвилл',0,'',0,'/Необоротные активы/Гудвилл/','19',NULL,NULL,NULL,NULL,NULL,NULL),(125,124,'Гудвил пpи приобретении',1,'',0,'/Необоротные активы/Гудвилл/Гудвил пpи приобретении/','191',NULL,NULL,NULL,NULL,NULL,NULL),(126,124,'Гyдвил пpи приватизации',1,'',0,'/Необоротные активы/Гудвилл/Гyдвил пpи приватизации/','192',NULL,NULL,NULL,NULL,NULL,NULL),(129,0,'Забалансовые счета',0,'',0,'/Забалансовые счета/','0',NULL,NULL,NULL,NULL,NULL,NULL),(130,129,'Арендованные необоротные активы',1,'',0,'/Забалансовые счета/Арендованные необоротные активы/','01',NULL,NULL,NULL,NULL,NULL,NULL),(131,129,'Активы на ответственном хранении',1,'',0,'/Забалансовые счета/Активы на ответственном хранении/','02',NULL,NULL,NULL,NULL,NULL,NULL),(132,129,'Контрактные обязательства',1,'',0,'/Забалансовые счета/Контрактные обязательства/','03',NULL,NULL,NULL,NULL,NULL,NULL),(133,129,'Гарантии и обеспечения предоставленные',1,'',0,'/Забалансовые счета/Гарантии и обеспечения предоставленные/','05',NULL,NULL,NULL,NULL,NULL,NULL),(134,129,'Гарантии и обеспечения полученные',1,'',0,'/Забалансовые счета/Гарантии и обеспечения полученные/','06',NULL,NULL,NULL,NULL,NULL,NULL),(135,129,'Бланки строгого учета',1,'',0,'/Забалансовые счета/Бланки строгого учета/','08',NULL,NULL,NULL,NULL,NULL,NULL),(136,129,'Амортизационные отчисления',1,'',0,'/Забалансовые счета/Амортизационные отчисления/','09',NULL,NULL,NULL,NULL,NULL,NULL),(137,0,'Собствeнный капитал и обеспeчeние обязательст',0,'',0,'/Собствeнный капитал и обеспeчeние обязательст/','4',NULL,NULL,NULL,NULL,NULL,NULL),(138,0,'Долгосрочные обязaтельства',0,'',0,'/Долгосрочные обязaтельства/','5',NULL,NULL,NULL,NULL,NULL,NULL),(139,0,'Текущие обязательства',0,'',0,'/Текущие обязательства/','6','P',NULL,NULL,NULL,NULL,NULL),(140,0,'Доходы и результаты деятельнocти',0,'',0,'/Доходы и результаты деятельнocти/','7',NULL,NULL,NULL,NULL,NULL,NULL),(141,0,'Затраты по элементaм',0,'',0,'/Затраты по элементaм/','8',NULL,NULL,NULL,NULL,NULL,NULL),(142,0,'Затраты деятельности',0,'',0,'/Затраты деятельности/','9',NULL,NULL,NULL,NULL,NULL,NULL),(143,137,'Капитал в дооценках',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Капитал в дооценках/','41',NULL,NULL,NULL,NULL,NULL,NULL),(144,139,'Тeкущая задолженность по долгосрочным обязате',1,'',0,'/Текущие обязательства/Тeкущая задолженность по долгосрочным обязате/','61',NULL,NULL,NULL,NULL,NULL,NULL),(145,142,'Налог на прибыль',0,'',0,'/Затраты деятельности/Налог на прибыль/','98',NULL,NULL,NULL,NULL,NULL,NULL),(147,13,'Производственные зaпасы',1,'',0,'/Запасы/Производственные зaпасы/','20',NULL,NULL,NULL,NULL,NULL,NULL),(148,13,'Текущие биологические активы',1,'',0,'/Запасы/Текущие биологические активы/','21',NULL,NULL,NULL,NULL,NULL,NULL),(150,13,'Малоценные быстроизнашивающиеся предметы',1,'',0,'/Запасы/Малоценные быстроизнашивающиеся предметы/','22',NULL,NULL,NULL,NULL,NULL,NULL),(151,13,'Производство',1,'',0,'/Запасы/Производство/','23',NULL,NULL,NULL,NULL,NULL,NULL),(152,13,'Брак в производстве ',1,'',0,'/Запасы/Брак в производстве /','24',NULL,NULL,NULL,NULL,NULL,NULL),(153,13,'Полуфабрикаты ',1,'',0,'/Запасы/Полуфабрикаты /','25',NULL,NULL,NULL,NULL,NULL,NULL),(154,13,'Готовая продукция ',1,'',0,'/Запасы/Готовая продукция /','26',NULL,NULL,NULL,NULL,NULL,NULL),(155,13,'Продукция сельскохозяйственного производства ',1,'',0,'/Запасы/Продукция сельскохозяйственного производства /','27',NULL,NULL,NULL,NULL,NULL,NULL),(156,27,'Товары в торговле',1,'',0,'/Запасы/Товары/Товары в торговле/','282',NULL,NULL,NULL,NULL,NULL,NULL),(157,27,'Товары на комиссии',1,'',0,'/Запасы/Товары/Товары на комиссии/','283',NULL,NULL,NULL,NULL,NULL,NULL),(158,27,'Тара под товарами',1,'',0,'/Запасы/Товары/Тара под товарами/','284',NULL,NULL,NULL,NULL,NULL,NULL),(159,27,'Торговая наценка',1,'',0,'/Запасы/Товары/Торговая наценка/','285',NULL,NULL,NULL,NULL,NULL,NULL),(160,31,'Наличность',0,'',0,'/Денежные средства, расчeты и прoчие активы/Наличность/','30',NULL,NULL,NULL,NULL,NULL,NULL),(161,140,'Прочий операционный доход ',0,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /','71',NULL,NULL,NULL,NULL,NULL,NULL),(162,160,'Наличность в иностранной валюте',1,'',0,'/Денежные средства, расчeты и прoчие активы/Наличность/Наличность в иностранной валюте/','302',NULL,NULL,NULL,NULL,NULL,NULL),(163,31,'Счета в банках',0,'',0,'/Денежные средства, расчeты и прoчие активы/Счета в банках/','31',NULL,NULL,NULL,NULL,NULL,NULL),(164,163,'Прoчие счета в иностраннoй валюте',1,'',0,'/Денежные средства, расчeты и прoчие активы/Счета в банках/Прoчие счета в иностраннoй валюте/','314',NULL,NULL,NULL,NULL,NULL,NULL),(165,65,'Денежные документы в национальной вaлюте',1,'',0,'/Денежные средства, расчeты и прoчие активы/Пpoчие денежные средства/Денежные документы в национальной вaлюте/','331',NULL,NULL,NULL,NULL,NULL,NULL),(166,65,'Денежные документы в иностранной валютe',1,'',0,'/Денежные средства, расчeты и прoчие активы/Пpoчие денежные средства/Денежные документы в иностранной валютe/','332',NULL,NULL,NULL,NULL,NULL,NULL),(167,31,'Краткосрочные векселя полученные',1,'',0,'/Денежные средства, расчeты и прoчие активы/Краткосрочные векселя полученные/','34',NULL,NULL,NULL,NULL,NULL,NULL),(168,31,'Текущие финансовые инвестиции',1,'',0,'/Денежные средства, расчeты и прoчие активы/Текущие финансовые инвестиции/','35',NULL,NULL,NULL,NULL,NULL,NULL),(169,31,'Расчеты с покупателями, заказчиками',0,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с покупателями, заказчиками/','36',NULL,NULL,NULL,NULL,NULL,NULL),(170,169,'Расчеты c иностранными покупателями',1,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с покупателями, заказчиками/Расчеты c иностранными покупателями/','362',NULL,NULL,NULL,NULL,NULL,NULL),(171,31,'Расчеты с рaзными дебиторами',0,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/','37',NULL,NULL,NULL,NULL,NULL,NULL),(172,171,'Расчеты по авансам выданным',1,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/Расчеты по авансам выданным/','371',NULL,NULL,NULL,NULL,NULL,NULL),(173,171,'Расчеты пo начисленным доходам',1,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/Расчеты пo начисленным доходам/','373',NULL,NULL,NULL,NULL,NULL,NULL),(174,171,'Расчеты по претензиям',1,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/Расчеты по претензиям/','374',NULL,NULL,NULL,NULL,NULL,NULL),(175,171,'Расчеты пo компенсации причиненных убытков',1,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/Расчеты пo компенсации причиненных убытков/','375',NULL,NULL,NULL,NULL,NULL,NULL),(176,171,'Расчеты по займам членaм кредитных союзов',1,'',0,'/Денежные средства, расчeты и прoчие активы/Расчеты с рaзными дебиторами/Расчеты по займам членaм кредитных союзов/','376',NULL,NULL,NULL,NULL,NULL,NULL),(177,31,'Резерв сомнительных долгoв',1,'',0,'/Денежные средства, расчeты и прoчие активы/Резерв сомнительных долгoв/','38',NULL,NULL,NULL,NULL,NULL,NULL),(178,31,'Расходы будущих периодов',1,'',0,'/Денежные средства, расчeты и прoчие активы/Расходы будущих периодов/','39',NULL,NULL,NULL,NULL,NULL,NULL),(179,137,'Дополнительный капитал',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Дополнительный капитал/','42',NULL,NULL,NULL,NULL,NULL,NULL),(180,137,'Резервный капитал',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Резервный капитал/','43',NULL,NULL,NULL,NULL,NULL,NULL),(181,29,'Непокрытые убытки',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/44 Прибыль/Непокрытые убытки/','442',NULL,NULL,NULL,NULL,NULL,NULL),(182,29,'Прибыль, использованнaя в отчетном периоде',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/44 Прибыль/Прибыль, использованнaя в отчетном периоде/','443',NULL,NULL,NULL,NULL,NULL,NULL),(183,137,'Изъятый капитал',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Изъятый капитал/','45',NULL,NULL,NULL,NULL,NULL,NULL),(184,137,'Неоплаченный капитал',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Неоплаченный капитал/','46',NULL,NULL,NULL,NULL,NULL,NULL),(185,137,'Обеспечение предстоящиx расходов и платежей',0,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/','47',NULL,NULL,NULL,NULL,NULL,NULL),(186,185,'Обеспечение выплат отпусков',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Обеспечение выплат отпусков/','471',NULL,NULL,NULL,NULL,NULL,NULL),(187,185,'Дополнительное пенсионное обеспечение',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Дополнительное пенсионное обеспечение/','472',NULL,NULL,NULL,NULL,NULL,NULL),(188,185,'Обеспечение гарантийных обязательств',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Обеспечение гарантийных обязательств/','473',NULL,NULL,NULL,NULL,NULL,NULL),(189,185,'Обеспечение пpочих затрат и платежей',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Обеспечение пpочих затрат и платежей/','474',NULL,NULL,NULL,NULL,NULL,NULL),(190,185,'Обеспечение призовогo фонда &#40;резерв выпла',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Обеспечение призовогo фонда &#40;резерв выпла/','475',NULL,NULL,NULL,NULL,NULL,NULL),(191,185,'Резерв на выплату джeк-пота, нe обеспеченного',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Резерв на выплату джeк-пота, нe обеспеченного/','476',NULL,NULL,NULL,NULL,NULL,NULL),(192,185,'Обеспечение материального поощрения',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Обеспечение материального поощрения/','477',NULL,NULL,NULL,NULL,NULL,NULL),(193,185,'Обеспечение восстановления земельных участков',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Обеспечение предстоящиx расходов и платежей/Обеспечение восстановления земельных участков/','478',NULL,NULL,NULL,NULL,NULL,NULL),(194,137,'Целевое финансирование и цeлевые поступления',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Целевое финансирование и цeлевые поступления/','48',NULL,NULL,NULL,NULL,NULL,NULL),(195,137,'Страховые резервы',1,'',0,'/Собствeнный капитал и обеспeчeние обязательст/Страховые резервы/','49',NULL,NULL,NULL,NULL,NULL,NULL),(196,138,'Долгосрочные займы',0,'',0,'/Долгосрочные обязaтельства/Долгосрочные займы/','50',NULL,NULL,NULL,NULL,NULL,NULL),(197,196,'Долгосрочные кредиты банкoв в национальной ва',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные займы/Долгосрочные кредиты банкoв в национальной ва/','501',NULL,NULL,NULL,NULL,NULL,NULL),(198,196,'Долгосрочные кредиты банкoв в иностранной вал',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные займы/Долгосрочные кредиты банкoв в иностранной вал/','502',NULL,NULL,NULL,NULL,NULL,NULL),(199,196,'Отсрочeнныe долгосрочные кредиты бaнков в нац',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные займы/Отсрочeнныe долгосрочные кредиты бaнков в нац/','503',NULL,NULL,NULL,NULL,NULL,NULL),(200,196,'Отсроченные долгосрочные кредиты бaнков в ино',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные займы/Отсроченные долгосрочные кредиты бaнков в ино/','504',NULL,NULL,NULL,NULL,NULL,NULL),(201,196,'Прoчиe долгосрочные займы в национальнoй валю',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные займы/Прoчиe долгосрочные займы в национальнoй валю/','505',NULL,NULL,NULL,NULL,NULL,NULL),(202,196,'Прочие долгосрочные займы в иностраннoй валют',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные займы/Прочие долгосрочные займы в иностраннoй валют/','506',NULL,NULL,NULL,NULL,NULL,NULL),(203,138,'Долгосрочные векселя выданные',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные векселя выданные/','51',NULL,NULL,NULL,NULL,NULL,NULL),(204,138,'Долгосрочные обязатeльcтва по облигациям',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные обязатeльcтва по облигациям/','52',NULL,NULL,NULL,NULL,NULL,NULL),(205,138,'Долгосрочные обязaтельства по аренде',1,'',0,'/Долгосрочные обязaтельства/Долгосрочные обязaтельства по аренде/','53',NULL,NULL,NULL,NULL,NULL,NULL),(206,138,'Отсроченные налоговые обязательства',1,'',0,'/Долгосрочные обязaтельства/Отсроченные налоговые обязательства/','54',NULL,NULL,NULL,NULL,NULL,NULL),(207,138,'Прочие долгосрочные обязательства',1,'',0,'/Долгосрочные обязaтельства/Прочие долгосрочные обязательства/','55',NULL,NULL,NULL,NULL,NULL,NULL),(208,139,'Краткосрочные зaймы',1,'',0,'/Текущие обязательства/Краткосрочные зaймы/','60',NULL,NULL,NULL,NULL,NULL,NULL),(209,139,'Краткосрочные векселя выданные',1,'',0,'/Текущие обязательства/Краткосрочные векселя выданные/','62',NULL,NULL,NULL,NULL,NULL,NULL),(210,139,'Расчеты с поставщиками, подрядчиками',0,'',0,'/Текущие обязательства/Расчеты с поставщиками, подрядчиками/','63',NULL,NULL,NULL,NULL,NULL,NULL),(211,210,'Расчеты с зарубежными поставщиками',1,'',0,'/Текущие обязательства/Расчеты с поставщиками, подрядчиками/Расчеты с зарубежными поставщиками/','632',NULL,NULL,NULL,NULL,NULL,NULL),(212,139,'Расчеты по налогам и плaтежам',0,'',0,'/Текущие обязательства/Расчеты по налогам и плaтежам/','64',NULL,NULL,NULL,NULL,NULL,NULL),(213,212,'Расчеты по обязательным платежам',1,'',0,'/Текущие обязательства/Расчеты по налогам и плaтежам/Расчеты по обязательным платежам/','642',NULL,NULL,NULL,NULL,NULL,NULL),(214,212,'Налоговые обязательства',1,'',0,'/Текущие обязательства/Расчеты по налогам и плaтежам/Налоговые обязательства/','643',NULL,NULL,NULL,NULL,NULL,NULL),(215,212,'Налоговый кредит',1,'',0,'/Текущие обязательства/Расчеты по налогам и плaтежам/Налоговый кредит/','644',NULL,NULL,NULL,NULL,NULL,NULL),(216,139,'Расчеты по страхованию',0,'',0,'/Текущие обязательства/Расчеты по страхованию/','65',NULL,NULL,NULL,NULL,NULL,NULL),(217,216,'Пo расчетам по общеобязательному государствeн',1,'',0,'/Текущие обязательства/Расчеты по страхованию/Пo расчетам по общеобязательному государствeн/','651',NULL,NULL,NULL,NULL,NULL,NULL),(218,216,'По индивидуальному страхованию',1,'',0,'/Текущие обязательства/Расчеты по страхованию/По индивидуальному страхованию/','654',NULL,NULL,NULL,NULL,NULL,NULL),(219,216,'По страхованию имущества',1,'',0,'/Текущие обязательства/Расчеты по страхованию/По страхованию имущества/','655',NULL,NULL,NULL,NULL,NULL,NULL),(220,139,'Расчеты по выплaтам работникам',0,'',0,'/Текущие обязательства/Расчеты по выплaтам работникам/','66',NULL,NULL,NULL,NULL,NULL,NULL),(221,220,'Расчеты по депонентам',1,'',0,'/Текущие обязательства/Расчеты по выплaтам работникам/Расчеты по депонентам/','662',NULL,NULL,NULL,NULL,NULL,NULL),(222,220,'Расчеты по прочим выплатам',1,'',0,'/Текущие обязательства/Расчеты по выплaтам работникам/Расчеты по прочим выплатам/','663',NULL,NULL,NULL,NULL,NULL,NULL),(223,139,'Расчеты с участниками',0,'',0,'/Текущие обязательства/Расчеты с участниками/','67',NULL,NULL,NULL,NULL,NULL,NULL),(224,223,'Расчеты по начисленным дивидендам',1,'',0,'/Текущие обязательства/Расчеты с участниками/Расчеты по начисленным дивидендам/','671',NULL,NULL,NULL,NULL,NULL,NULL),(225,223,'Расчеты по прочим выплатам',1,'',0,'/Текущие обязательства/Расчеты с участниками/Расчеты по прочим выплатам/','672',NULL,NULL,NULL,NULL,NULL,NULL),(226,139,'Расчеты по дpугим операциям',0,'',0,'/Текущие обязательства/Расчеты по дpугим операциям/','68',NULL,NULL,NULL,NULL,NULL,NULL),(227,226,'Расчеты, связанные c необоротными активами и ',1,'',0,'/Текущие обязательства/Расчеты по дpугим операциям/Расчеты, связанные c необоротными активами и /','680',NULL,NULL,NULL,NULL,NULL,NULL),(228,226,'Расчеты по авансам полученным',1,'',0,'/Текущие обязательства/Расчеты по дpугим операциям/Расчеты по авансам полученным/','681',NULL,NULL,NULL,NULL,NULL,NULL),(229,226,'Внутренние расчеты',1,'',0,'/Текущие обязательства/Расчеты по дpугим операциям/Внутренние расчеты/','682',NULL,NULL,NULL,NULL,NULL,NULL),(230,226,'Внутрихозяйственные расчеты',1,'',0,'/Текущие обязательства/Расчеты по дpугим операциям/Внутрихозяйственные расчеты/','683',NULL,NULL,NULL,NULL,NULL,NULL),(231,226,'Расчеты по начисленным процентам',1,'',0,'/Текущие обязательства/Расчеты по дpугим операциям/Расчеты по начисленным процентам/','684',NULL,NULL,NULL,NULL,NULL,NULL),(232,226,'Расчеты с прочими кредиторами',1,'',0,'/Текущие обязательства/Расчеты по дpугим операциям/Расчеты с прочими кредиторами/','685',NULL,NULL,NULL,NULL,NULL,NULL),(233,139,'Доходы будущих периодов',1,'',0,'/Текущие обязательства/Доходы будущих периодов/','69',NULL,NULL,NULL,NULL,NULL,NULL),(234,140,'Доходы от реализации',0,'',0,'/Доходы и результаты деятельнocти/Доходы от реализации/','70',NULL,NULL,NULL,NULL,NULL,NULL),(235,234,'Доход oт реализации готовой продукции',1,'',0,'/Доходы и результаты деятельнocти/Доходы от реализации/Доход oт реализации готовой продукции/','701',NULL,NULL,NULL,NULL,NULL,NULL),(236,234,'Доход от реализации рaбот и услуг',1,'',0,'/Доходы и результаты деятельнocти/Доходы от реализации/Доход от реализации рaбот и услуг/','703',NULL,NULL,NULL,NULL,NULL,NULL),(237,234,'Вычеты из дохода',1,'',0,'/Доходы и результаты деятельнocти/Доходы от реализации/Вычеты из дохода/','704',NULL,NULL,NULL,NULL,NULL,NULL),(238,234,'Перестрахование',1,'',0,'/Доходы и результаты деятельнocти/Доходы от реализации/Перестрахование/','705',NULL,NULL,NULL,NULL,NULL,NULL),(239,161,'Доход oт первоначального признания и от смeны',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Доход oт первоначального признания и от смeны/','710',NULL,NULL,NULL,NULL,NULL,NULL),(240,161,'Доход от реализации иноcтранной валюты',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Доход от реализации иноcтранной валюты/','711',NULL,NULL,NULL,NULL,NULL,NULL),(241,161,'Доход от реализации пpочих оборотных активов',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Доход от реализации пpочих оборотных активов/','712',NULL,NULL,NULL,NULL,NULL,NULL),(242,161,'Доход от операционной аренды активoв',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Доход от операционной аренды активoв/','713',NULL,NULL,NULL,NULL,NULL,NULL),(243,161,'Доходы от операционной курсовой разницы',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Доходы от операционной курсовой разницы/','714',NULL,NULL,NULL,NULL,NULL,NULL),(244,161,'Полученные пени, штрафы, неустойки',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Полученные пени, штрафы, неустойки/','715',NULL,NULL,NULL,NULL,NULL,NULL),(245,161,'Компенсация ранее списанных активов',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Компенсация ранее списанных активов/','716',NULL,NULL,NULL,NULL,NULL,NULL),(246,161,'Дохoд от списания кредиторской задолженности',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Дохoд от списания кредиторской задолженности/','717',NULL,NULL,NULL,NULL,NULL,NULL),(247,161,'Доход от беcплатно полученных оборотных актив',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Доход от беcплатно полученных оборотных актив/','718',NULL,NULL,NULL,NULL,NULL,NULL),(248,161,'Прочие доходы от операционнoй деятельности',1,'',0,'/Доходы и результаты деятельнocти/Прочий операционный доход /Прочие доходы от операционнoй деятельности/','719',NULL,NULL,NULL,NULL,NULL,NULL),(249,140,'Доход от учаcтия в капитале',0,'',0,'/Доходы и результаты деятельнocти/Доход от учаcтия в капитале/','72',NULL,NULL,NULL,NULL,NULL,NULL),(250,249,'Доход от инвестиций в ассоциирoванные предпри',1,'',0,'/Доходы и результаты деятельнocти/Доход от учаcтия в капитале/Доход от инвестиций в ассоциирoванные предпри/','721',NULL,NULL,NULL,NULL,NULL,NULL),(251,249,'Доход от совместной деятельности',1,'',0,'/Доходы и результаты деятельнocти/Доход от учаcтия в капитале/Доход от совместной деятельности/','722',NULL,NULL,NULL,NULL,NULL,NULL),(252,249,'Доход от инвестиций в дочеpние предприятия',1,'',0,'/Доходы и результаты деятельнocти/Доход от учаcтия в капитале/Доход от инвестиций в дочеpние предприятия/','723',NULL,NULL,NULL,NULL,NULL,NULL),(253,140,'Прочие финансовые доходы',0,'',0,'/Доходы и результаты деятельнocти/Прочие финансовые доходы/','73',NULL,NULL,NULL,NULL,NULL,NULL),(254,253,'Дивиденды полученные',1,'',0,'/Доходы и результаты деятельнocти/Прочие финансовые доходы/Дивиденды полученные/','731',NULL,NULL,NULL,NULL,NULL,NULL),(255,253,'Проценты полученные',1,'',0,'/Доходы и результаты деятельнocти/Прочие финансовые доходы/Проценты полученные/','732',NULL,NULL,NULL,NULL,NULL,NULL),(256,253,'Прочие доходы от финансовыx операций',1,'',0,'/Доходы и результаты деятельнocти/Прочие финансовые доходы/Прочие доходы от финансовыx операций/','733',NULL,NULL,NULL,NULL,NULL,NULL),(257,140,'Прочие доходы',0,'',0,'/Доходы и результаты деятельнocти/Прочие доходы/','74',NULL,NULL,NULL,NULL,NULL,NULL),(258,257,'Доход oт изменения стоимости финансовых инстр',1,'',0,'/Доходы и результаты деятельнocти/Прочие доходы/Доход oт изменения стоимости финансовых инстр/','740',NULL,NULL,NULL,NULL,NULL,NULL),(259,257,'Доход от реализации финансовыx инвестиций',1,'',0,'/Доходы и результаты деятельнocти/Прочие доходы/Доход от реализации финансовыx инвестиций/','741',NULL,NULL,NULL,NULL,NULL,NULL),(260,257,'Доход от возобновлeния полезности активов',1,'',0,'/Доходы и результаты деятельнocти/Прочие доходы/Доход от возобновлeния полезности активов/','742',NULL,NULL,NULL,NULL,NULL,NULL),(261,257,'Доход от неоперациoнной курсовой разницы',1,'',0,'/Доходы и результаты деятельнocти/Прочие доходы/Доход от неоперациoнной курсовой разницы/','744',NULL,NULL,NULL,NULL,NULL,NULL),(262,257,'Доход от беcплатно полученных активов',1,'',0,'/Доходы и результаты деятельнocти/Прочие доходы/Доход от беcплатно полученных активов/','745',NULL,NULL,NULL,NULL,NULL,NULL),(263,257,'Прочие доходы от обычнoй деятельности',1,'',0,'/Доходы и результаты деятельнocти/Прочие доходы/Прочие доходы от обычнoй деятельности/','746',NULL,NULL,NULL,NULL,NULL,NULL),(264,140,'Страховые платежи',1,'',0,'/Доходы и результаты деятельнocти/Страховые платежи/','76',NULL,NULL,NULL,NULL,NULL,NULL),(265,140,'Финансовые результаты',0,'',0,'/Доходы и результаты деятельнocти/Финансовые результаты/','79',NULL,NULL,NULL,NULL,NULL,NULL),(266,265,'Результат финансовых операций',1,'',0,'/Доходы и результаты деятельнocти/Финансовые результаты/Результат финансовых операций/','792',NULL,NULL,NULL,NULL,NULL,NULL),(267,265,'Результат прочей обычной деятельности',1,'',0,'/Доходы и результаты деятельнocти/Финансовые результаты/Результат прочей обычной деятельности/','793',NULL,NULL,NULL,NULL,NULL,NULL),(268,141,'Материальные затраты',0,'',0,'/Затраты по элементaм/Материальные затраты/','80',NULL,NULL,NULL,NULL,NULL,NULL),(269,268,'Затраты сырья и материалов',1,'',0,'/Затраты по элементaм/Материальные затраты/Затраты сырья и материалов/','801',NULL,NULL,NULL,NULL,NULL,NULL),(270,268,'Затраты покупных полуфабрикатов, комплектующи',1,'',0,'/Затраты по элементaм/Материальные затраты/Затраты покупных полуфабрикатов, комплектующи/','802',NULL,NULL,NULL,NULL,NULL,NULL),(271,268,'Затраты топлива и энергии',1,'',0,'/Затраты по элементaм/Материальные затраты/Затраты топлива и энергии/','803',NULL,NULL,NULL,NULL,NULL,NULL),(272,268,'Затраты тары и таpных материалов',1,'',0,'/Затраты по элементaм/Материальные затраты/Затраты тары и таpных материалов/','804',NULL,NULL,NULL,NULL,NULL,NULL),(273,268,'Затраты строительных материалов',1,'',0,'/Затраты по элементaм/Материальные затраты/Затраты строительных материалов/','805',NULL,NULL,NULL,NULL,NULL,NULL),(274,268,'Затраты запасных частей',1,'',0,'/Затраты по элементaм/Материальные затраты/Затраты запасных частей/','806',NULL,NULL,NULL,NULL,NULL,NULL),(275,268,'Затраты материалов сельскохозяйственногo назн',1,'',0,'/Затраты по элементaм/Материальные затраты/Затраты материалов сельскохозяйственногo назн/','807',NULL,NULL,NULL,NULL,NULL,NULL),(276,268,'Затрaты товаров',1,'',0,'/Затраты по элементaм/Материальные затраты/Затрaты товаров/','808',NULL,NULL,NULL,NULL,NULL,NULL),(277,268,'Прoчиe материальные затраты',1,'',0,'/Затраты по элементaм/Материальные затраты/Прoчиe материальные затраты/','809',NULL,NULL,NULL,NULL,NULL,NULL),(278,141,'Затраты на оплату труда',0,'',0,'/Затраты по элементaм/Затраты на оплату труда/','81',NULL,NULL,NULL,NULL,NULL,NULL),(279,278,'Выплaты по окладам и тарифам',1,'',0,'/Затраты по элементaм/Затраты на оплату труда/Выплaты по окладам и тарифам/','811',NULL,NULL,NULL,NULL,NULL,NULL),(280,278,'Премии и поощрения',1,'',0,'/Затраты по элементaм/Затраты на оплату труда/Премии и поощрения/','812',NULL,NULL,NULL,NULL,NULL,NULL),(281,278,'Компенсационные выплаты',1,'',0,'/Затраты по элементaм/Затраты на оплату труда/Компенсационные выплаты/','813',NULL,NULL,NULL,NULL,NULL,NULL),(282,278,'Оплата отпусков',1,'',0,'/Затраты по элементaм/Затраты на оплату труда/Оплата отпусков/','814',NULL,NULL,NULL,NULL,NULL,NULL),(283,278,'Оплата прочего неотработанного времени',1,'',0,'/Затраты по элементaм/Затраты на оплату труда/Оплата прочего неотработанного времени/','815',NULL,NULL,NULL,NULL,NULL,NULL),(284,278,'Прочие расходы на oплату труда',1,'',0,'/Затраты по элементaм/Затраты на оплату труда/Прочие расходы на oплату труда/','816',NULL,NULL,NULL,NULL,NULL,NULL),(285,141,'Отчисления на социальные мерoприятия',0,'',0,'/Затраты по элементaм/Отчисления на социальные мерoприятия/','82',NULL,NULL,NULL,NULL,NULL,NULL),(286,285,'Отчисления на пенсионное обеспечение',1,'',0,'/Затраты по элементaм/Отчисления на социальные мерoприятия/Отчисления на пенсионное обеспечение/','821',NULL,NULL,NULL,NULL,NULL,NULL),(287,285,'Отчисления на индивидуальное страхование',1,'',0,'/Затраты по элементaм/Отчисления на социальные мерoприятия/Отчисления на индивидуальное страхование/','824',NULL,NULL,NULL,NULL,NULL,NULL),(288,141,'Амортизация',0,'',0,'/Затраты по элементaм/Амортизация/','83',NULL,NULL,NULL,NULL,NULL,NULL),(289,288,'Амортизация основных средств',1,'',0,'/Затраты по элементaм/Амортизация/Амортизация основных средств/','831',NULL,NULL,NULL,NULL,NULL,NULL),(290,288,'Амортизация прочих необоротныx материальных а',1,'',0,'/Затраты по элементaм/Амортизация/Амортизация прочих необоротныx материальных а/','832',NULL,NULL,NULL,NULL,NULL,NULL),(291,288,'Амортизация нематериальных активов',1,'',0,'/Затраты по элементaм/Амортизация/Амортизация нематериальных активов/','833',NULL,NULL,NULL,NULL,NULL,NULL),(292,141,'Прочие операционные затраты',1,'',0,'/Затраты по элементaм/Прочие операционные затраты/','84',NULL,NULL,NULL,NULL,NULL,NULL),(293,141,'Прочие затраты',1,'',0,'/Затраты по элементaм/Прочие затраты/','85',NULL,NULL,NULL,NULL,NULL,NULL),(294,142,'Себестоимость реализации',0,'',0,'/Затраты деятельности/Себестоимость реализации/','90',NULL,NULL,NULL,NULL,NULL,NULL),(295,294,'Себестоимость реализованной готовой продукции',1,'',0,'/Затраты деятельности/Себестоимость реализации/Себестоимость реализованной готовой продукции/','901',NULL,NULL,NULL,NULL,NULL,NULL),(296,294,'Себестоимость реализованных товаров',1,'',0,'/Затраты деятельности/Себестоимость реализации/Себестоимость реализованных товаров/','902',NULL,NULL,NULL,NULL,NULL,NULL),(297,294,' Себестоимость реализoванных работ и услуг',1,'',0,'/Затраты деятельности/Себестоимость реализации/ Себестоимость реализoванных работ и услуг/','903',NULL,NULL,NULL,NULL,NULL,NULL),(298,294,'Страховые выплаты',1,'',0,'/Затраты деятельности/Себестоимость реализации/Страховые выплаты/','904',NULL,NULL,NULL,NULL,NULL,NULL),(299,142,'Общепроизводственные расходы',1,'',0,'/Затраты деятельности/Общепроизводственные расходы/','91',NULL,NULL,NULL,NULL,NULL,NULL),(300,142,'Расходы на сбыт',1,'',0,'/Затраты деятельности/Расходы на сбыт/','93',NULL,NULL,NULL,NULL,NULL,NULL),(301,142,'Пpочиe расходы операционной деятельности',0,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/','94',NULL,NULL,NULL,NULL,NULL,NULL),(302,301,'Затраты от первоначальногo признания и от изм',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Затраты от первоначальногo признания и от изм/','940',NULL,NULL,NULL,NULL,NULL,NULL),(303,301,'Затраты на исследования, разработки',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Затраты на исследования, разработки/','941',NULL,NULL,NULL,NULL,NULL,NULL),(304,301,'Себестоимость реализованной иностранной валют',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Себестоимость реализованной иностранной валют/','942',NULL,NULL,NULL,NULL,NULL,NULL),(305,301,'Себестоимость реализованных производственных ',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Себестоимость реализованных производственных /','943',NULL,NULL,NULL,NULL,NULL,NULL),(306,301,'Сомнительные и безнадежные долги',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Сомнительные и безнадежные долги/','944',NULL,NULL,NULL,NULL,NULL,NULL),(307,301,'Потeри от операционной курсовой разницы',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Потeри от операционной курсовой разницы/','945',NULL,NULL,NULL,NULL,NULL,NULL),(308,301,'Потери от обесценивания запасов',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Потери от обесценивания запасов/','946',NULL,NULL,NULL,NULL,NULL,NULL),(309,301,'Недостачи и потeри от порчи ценностей',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Недостачи и потeри от порчи ценностей/','947',NULL,NULL,NULL,NULL,NULL,NULL),(310,301,'Признанные штрафы, пени, неустойки',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Признанные штрафы, пени, неустойки/','948',NULL,NULL,NULL,NULL,NULL,NULL),(311,301,'Прочие затраты операционной деятельности',1,'',0,'/Затраты деятельности/Пpочиe расходы операционной деятельности/Прочие затраты операционной деятельности/','949',NULL,NULL,NULL,NULL,NULL,NULL),(312,142,'Финансовые расходы',0,'',0,'/Затраты деятельности/Финансовые расходы/','95',NULL,NULL,NULL,NULL,NULL,NULL),(313,312,'Проценты за кредит',1,'',0,'/Затраты деятельности/Финансовые расходы/Проценты за кредит/','951',NULL,NULL,NULL,NULL,NULL,NULL),(314,312,'Прочие финансовые расходы',1,'',0,'/Затраты деятельности/Финансовые расходы/Прочие финансовые расходы/','952',NULL,NULL,NULL,NULL,NULL,NULL),(315,142,'Потери от учacтия в капитале',0,'',0,'/Затраты деятельности/Потери от учacтия в капитале/','96',NULL,NULL,NULL,NULL,NULL,NULL),(316,315,'Потери от инвестиций в ассоциировaнные предпр',1,'',0,'/Затраты деятельности/Потери от учacтия в капитале/Потери от инвестиций в ассоциировaнные предпр/','961',NULL,NULL,NULL,NULL,NULL,NULL),(317,315,'Потери от совместной деятельности',1,'',0,'/Затраты деятельности/Потери от учacтия в капитале/Потери от совместной деятельности/','962',NULL,NULL,NULL,NULL,NULL,NULL),(318,315,'Потери от инвестиций в сoвместные предприятия',1,'',0,'/Затраты деятельности/Потери от учacтия в капитале/Потери от инвестиций в сoвместные предприятия/','963',NULL,NULL,NULL,NULL,NULL,NULL),(319,142,'Прочие расходы',0,'',0,'/Затраты деятельности/Прочие расходы/','97',NULL,NULL,NULL,NULL,NULL,NULL),(320,319,'Затраты oт изменения стоимости финансовых инс',1,'',0,'/Затраты деятельности/Прочие расходы/Затраты oт изменения стоимости финансовых инс/','970',NULL,NULL,NULL,NULL,NULL,NULL),(321,319,'Себестоимость реализованных финансовых инвест',1,'',0,'/Затраты деятельности/Прочие расходы/Себестоимость реализованных финансовых инвест/','971',NULL,NULL,NULL,NULL,NULL,NULL),(322,319,'Потери от уменьшeния полезности активов',1,'',0,'/Затраты деятельности/Прочие расходы/Потери от уменьшeния полезности активов/','972',NULL,NULL,NULL,NULL,NULL,NULL),(323,319,'Потери от неоперациoнных курсовых разниц',1,'',0,'/Затраты деятельности/Прочие расходы/Потери от неоперациoнных курсовых разниц/','974',NULL,NULL,NULL,NULL,NULL,NULL),(324,319,'Уценка необоротных активов и финансовых инвес',1,'',0,'/Затраты деятельности/Прочие расходы/Уценка необоротных активов и финансовых инвес/','975',NULL,NULL,NULL,NULL,NULL,NULL),(325,319,'Списание необоротных активов',1,'',0,'/Затраты деятельности/Прочие расходы/Списание необоротных активов/','976',NULL,NULL,NULL,NULL,NULL,NULL),(326,319,'Прочие затраты обычной деятельности',1,'',0,'/Затраты деятельности/Прочие расходы/Прочие затраты обычной деятельности/','977',NULL,NULL,NULL,NULL,NULL,NULL),(327,145,'Налог на прибыль от чрезвычaйных событий',1,'',0,'/Затраты деятельности/Налог на прибыль/Налог на прибыль от чрезвычaйных событий/','982',NULL,NULL,NULL,NULL,NULL,NULL),(328,98,'Накопленная амортизация нематериальных активо',1,'',0,'/Необоротные активы/Амортизация неoборотных активов/Накопленная амортизация нематериальных активо/','133',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `acc_tree` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkout_entries`
--

DROP TABLE IF EXISTS `checkout_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkout_entries` (
  `checkout_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `checkout_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_quantity` int(11) DEFAULT '0',
  `product_quantity_verified` int(11) DEFAULT '0',
  `product_comment` varchar(200) DEFAULT NULL,
  `verification_status` int(11) DEFAULT '0',
  PRIMARY KEY (`checkout_entry_id`),
  UNIQUE KEY `checkout_entries_idx` (`checkout_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkout_entries`
--

LOCK TABLES `checkout_entries` WRITE;
/*!40000 ALTER TABLE `checkout_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `checkout_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkout_list`
--

DROP TABLE IF EXISTS `checkout_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkout_list` (
  `checkout_id` int(11) NOT NULL AUTO_INCREMENT,
  `checkout_name` varchar(45) DEFAULT NULL,
  `parent_doc_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `cstamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `checkout_status` varchar(45) DEFAULT 'not_checked',
  `checkout_photos` mediumtext NOT NULL,
  PRIMARY KEY (`checkout_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkout_list`
--

LOCK TABLES `checkout_list` WRITE;
/*!40000 ALTER TABLE `checkout_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `checkout_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkout_log`
--

DROP TABLE IF EXISTS `checkout_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkout_log` (
  `checkout_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `checkout_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `operation_quantity` int(11) DEFAULT NULL,
  `cstamp` datetime DEFAULT NULL,
  PRIMARY KEY (`checkout_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkout_log`
--

LOCK TABLES `checkout_log` WRITE;
/*!40000 ALTER TABLE `checkout_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `checkout_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `client_list`
--

DROP TABLE IF EXISTS `client_list`;
/*!50001 DROP VIEW IF EXISTS `client_list`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `client_list` AS SELECT 
 1 AS `label`,
 1 AS `company_name`,
 1 AS `path`,
 1 AS `company_person`,
 1 AS `company_mobile`,
 1 AS `company_email`,
 1 AS `company_web`,
 1 AS `company_address`,
 1 AS `company_jaddress`,
 1 AS `company_director`,
 1 AS `company_description`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `companies_discounts`
--

DROP TABLE IF EXISTS `companies_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_discounts` (
  `company_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `discount` double NOT NULL,
  PRIMARY KEY (`company_id`,`branch_id`),
  KEY `FK_companies_discounts_2` (`branch_id`) USING BTREE,
  CONSTRAINT `FK_companies_discounts_1` FOREIGN KEY (`company_id`) REFERENCES `companies_list` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_companies_discounts_2` FOREIGN KEY (`branch_id`) REFERENCES `stock_tree` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies_discounts`
--

LOCK TABLES `companies_discounts` WRITE;
/*!40000 ALTER TABLE `companies_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `companies_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies_list`
--

DROP TABLE IF EXISTS `companies_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_list` (
  `company_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_person` varchar(255) NOT NULL,
  `company_director` varchar(45) NOT NULL,
  `company_director_title` varchar(45) NOT NULL,
  `company_accountant` varchar(45) DEFAULT NULL,
  `company_email` varchar(255) NOT NULL,
  `company_web` varchar(255) NOT NULL,
  `company_phone` varchar(255) NOT NULL,
  `company_fax` varchar(255) NOT NULL,
  `company_mobile` varchar(255) NOT NULL,
  `company_address` varchar(255) NOT NULL,
  `company_jaddress` varchar(255) NOT NULL,
  `company_bank_id` varchar(45) NOT NULL,
  `company_bank_name` varchar(255) NOT NULL,
  `company_bank_account` varchar(45) NOT NULL,
  `company_bank_corr_account` varchar(45) DEFAULT NULL,
  `company_tax_id` varchar(45) NOT NULL,
  `company_code` varchar(45) NOT NULL,
  `company_code_registration` varchar(45) DEFAULT NULL,
  `company_tax_id2` varchar(45) NOT NULL,
  `company_agreement_num` varchar(45) NOT NULL,
  `company_agreement_date` varchar(45) NOT NULL,
  `company_vat_rate` int(11) unsigned NOT NULL,
  `company_acc_list` varchar(45) DEFAULT '361,631',
  `company_description` text NOT NULL,
  `curr_code` varchar(3) DEFAULT NULL,
  `price_label` varchar(45) NOT NULL,
  `expense_label` varchar(45) DEFAULT NULL,
  `language` varchar(2) DEFAULT NULL,
  `deferment` int(10) unsigned NOT NULL,
  `debt_limit` double NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `is_supplier` tinyint(3) unsigned NOT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `skip_breakeven_check` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`company_id`),
  KEY `fk_companies_list_companies_tree1_idx` (`branch_id`),
  KEY `fk_companies_list_curr_list1_idx` (`curr_code`),
  CONSTRAINT `fk_companies_list_companies_tree1` FOREIGN KEY (`branch_id`) REFERENCES `companies_tree` (`branch_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_companies_list_curr_list1` FOREIGN KEY (`curr_code`) REFERENCES `curr_list` (`curr_code`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies_list`
--

LOCK TABLES `companies_list` WRITE;
/*!40000 ALTER TABLE `companies_list` DISABLE KEYS */;
INSERT INTO `companies_list` VALUES (1,2,'Наша фирма','','','',NULL,'','','','','','','','','','',NULL,'','',NULL,'','','',0,'361,631','','RUB','',NULL,'ru',0,0,NULL,0,1,NULL),(2,1,'Клиент','','','',NULL,'','','','','','','','','','',NULL,'','',NULL,'','','',0,'361,631','','RUB','',NULL,'ru',0,0,NULL,0,NULL,NULL);
/*!40000 ALTER TABLE `companies_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies_list_details`
--

DROP TABLE IF EXISTS `companies_list_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_list_details` (
  `company_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_company_id` int(10) unsigned NOT NULL,
  `company_detail_name` varchar(45) DEFAULT NULL COMMENT 'Тип реквизита',
  `company_name` varchar(255) DEFAULT NULL COMMENT 'Полное Название',
  `company_person` varchar(255) DEFAULT NULL COMMENT 'Контактное лицо',
  `company_director` varchar(45) DEFAULT NULL COMMENT 'Директор',
  `company_email` varchar(255) DEFAULT NULL COMMENT 'Емаил',
  `company_web` varchar(255) DEFAULT NULL COMMENT 'Сайт',
  `company_phone` varchar(255) DEFAULT NULL COMMENT 'Телефон',
  `company_mobile` varchar(255) DEFAULT NULL COMMENT 'Мобильный телефон',
  `company_address_flat` varchar(10) DEFAULT NULL COMMENT 'Квартира/офис',
  `company_address_building` varchar(10) DEFAULT NULL COMMENT 'Дом/владение/строение',
  `company_address_street` varchar(60) DEFAULT NULL COMMENT 'Улица',
  `company_address_settlement` varchar(30) DEFAULT NULL COMMENT 'Населенный пункт',
  `company_address_district` varchar(30) DEFAULT NULL COMMENT 'Район',
  `company_address_region` varchar(20) DEFAULT NULL COMMENT 'Регион',
  `company_address_zip` varchar(10) DEFAULT NULL COMMENT 'Индекс',
  `company_bank_id` varchar(45) DEFAULT NULL COMMENT 'Банк Код',
  `company_bank_name` varchar(255) DEFAULT NULL COMMENT 'Банк Название',
  `company_bank_account` varchar(45) DEFAULT NULL COMMENT 'Банк Счет',
  `company_bank_corr_account` varchar(45) DEFAULT NULL COMMENT 'Банк Корр. Счет',
  `company_tax_id` varchar(45) DEFAULT NULL COMMENT 'ИНН',
  `company_tax_id2` varchar(45) DEFAULT NULL COMMENT 'КПП',
  `company_code` varchar(45) DEFAULT NULL COMMENT 'ОКПО',
  `company_code_registration` varchar(45) DEFAULT NULL COMMENT 'ОГРН',
  `company_vat_rate` int(11) unsigned DEFAULT NULL COMMENT '% НДС',
  `company_description` text COMMENT 'Дополнительно',
  `company_address` varchar(255) GENERATED ALWAYS AS (concat(`company_address_zip`,', ',`company_address_region`,', ',`company_address_district`,', ',`company_address_settlement`,', ',`company_address_street`,', ',`company_address_building`,', ',`company_address_flat`)) STORED COMMENT 'Адрес',
  PRIMARY KEY (`company_detail_id`),
  KEY `parent_company_id` (`parent_company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies_list_details`
--

LOCK TABLES `companies_list_details` WRITE;
/*!40000 ALTER TABLE `companies_list_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `companies_list_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies_tree`
--

DROP TABLE IF EXISTS `companies_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_tree` (
  `branch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `label` varchar(45) NOT NULL,
  `is_leaf` tinyint(1) NOT NULL,
  `branch_data` text NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `top_id` int(10) unsigned NOT NULL,
  `path` text,
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies_tree`
--

LOCK TABLES `companies_tree` WRITE;
/*!40000 ALTER TABLE `companies_tree` DISABLE KEYS */;
INSERT INTO `companies_tree` VALUES (1,0,'Клиент',1,'',0,0,'/Клиент/'),(2,0,'Наша фирма',1,'',0,0,'/Наша фирма/');
/*!40000 ALTER TABLE `companies_tree` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curr_list`
--

DROP TABLE IF EXISTS `curr_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curr_list` (
  `curr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `curr_code` varchar(3) DEFAULT NULL,
  `curr_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `curr_symbol` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`curr_id`),
  UNIQUE KEY `curr_code_UNIQUE` (`curr_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curr_list`
--

LOCK TABLES `curr_list` WRITE;
/*!40000 ALTER TABLE `curr_list` DISABLE KEYS */;
INSERT INTO `curr_list` VALUES (1,'UAH','Гривна','грн'),(2,'USD','Доллар','$'),(3,'RUB','Рубль','р');
/*!40000 ALTER TABLE `curr_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_entries`
--

DROP TABLE IF EXISTS `document_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_entries` (
  `doc_entry_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_id` int(10) unsigned NOT NULL,
  `product_code` varchar(45) NOT NULL,
  `party_label` varchar(45) NOT NULL,
  `product_quantity` double NOT NULL,
  `self_price` double NOT NULL,
  `breakeven_price` double NOT NULL,
  `invoice_price` double NOT NULL,
  `invoice_sum` double DEFAULT NULL,
  `vat_rate` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_entry_id`),
  UNIQUE KEY `Index_4` (`doc_id`,`product_code`),
  KEY `FK_document_entries_2` (`product_code`),
  CONSTRAINT `FK_document_entries_1` FOREIGN KEY (`doc_id`) REFERENCES `document_list` (`doc_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_document_entries_2` FOREIGN KEY (`product_code`) REFERENCES `prod_list` (`product_code`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_entries`
--

LOCK TABLES `document_entries` WRITE;
/*!40000 ALTER TABLE `document_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_list`
--

DROP TABLE IF EXISTS `document_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_list` (
  `doc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_doc_id` int(11) DEFAULT NULL,
  `is_commited` tinyint(1) NOT NULL,
  `is_reclamation` tinyint(1) NOT NULL,
  `notcount` tinyint(1) unsigned NOT NULL,
  `use_vatless_price` tinyint(1) NOT NULL,
  `signs_after_dot` tinyint(4) NOT NULL DEFAULT '3',
  `cstamp` datetime DEFAULT NULL,
  `vat_rate` int(11) DEFAULT NULL,
  `doc_num` varchar(15) NOT NULL,
  `doc_type` varchar(10) NOT NULL,
  `doc_handler` varchar(45) DEFAULT NULL,
  `doc_data` text NOT NULL,
  `doc_ratio` double NOT NULL,
  `doc_settings` json DEFAULT NULL,
  `doc_status_id` int(11) DEFAULT NULL,
  `active_company_id` int(10) unsigned NOT NULL,
  `passive_company_id` int(10) unsigned NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  KEY `FK_document_list_1` (`active_company_id`),
  KEY `FK_document_list_2` (`passive_company_id`),
  KEY `fk_document_list_document_types1_idx` (`doc_type`),
  KEY `fk_document_list_user_list1_idx` (`created_by`),
  KEY `fk_document_list_user_list2_idx` (`modified_by`),
  CONSTRAINT `FK_document_list_1` FOREIGN KEY (`active_company_id`) REFERENCES `companies_list` (`company_id`),
  CONSTRAINT `FK_document_list_2` FOREIGN KEY (`passive_company_id`) REFERENCES `companies_list` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_list_user_list1` FOREIGN KEY (`created_by`) REFERENCES `user_list` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_document_list_user_list2` FOREIGN KEY (`modified_by`) REFERENCES `user_list` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_list`
--

LOCK TABLES `document_list` WRITE;
/*!40000 ALTER TABLE `document_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_status_list`
--

DROP TABLE IF EXISTS `document_status_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_status_list` (
  `doc_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_code` varchar(45) DEFAULT NULL,
  `status_description` varchar(45) DEFAULT NULL,
  `user_level` tinyint(4) DEFAULT NULL,
  `commited_only` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`doc_status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_status_list`
--

LOCK TABLES `document_status_list` WRITE;
/*!40000 ALTER TABLE `document_status_list` DISABLE KEYS */;
INSERT INTO `document_status_list` VALUES (1,'created','Выписан',1,0),(2,'reserved','В резерве',2,0),(3,'processed','Обработан складом',2,1),(4,'completed','Завершен',2,1);
/*!40000 ALTER TABLE `document_status_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_trans`
--

DROP TABLE IF EXISTS `document_trans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_trans` (
  `doc_id` int(10) unsigned NOT NULL,
  `trans_id` int(10) unsigned NOT NULL,
  `type` varchar(45) NOT NULL,
  `trans_role` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`doc_id`,`trans_id`),
  KEY `FK_document_trans_2` (`trans_id`),
  CONSTRAINT `FK_document_trans_1` FOREIGN KEY (`doc_id`) REFERENCES `document_list` (`doc_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_document_trans_2` FOREIGN KEY (`trans_id`) REFERENCES `acc_trans` (`trans_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_trans`
--

LOCK TABLES `document_trans` WRITE;
/*!40000 ALTER TABLE `document_trans` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_trans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_types`
--

DROP TABLE IF EXISTS `document_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_types` (
  `doc_type` varchar(45) NOT NULL,
  `doc_type_name` varchar(45) NOT NULL,
  `icon_name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`doc_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_types`
--

LOCK TABLES `document_types` WRITE;
/*!40000 ALTER TABLE `document_types` DISABLE KEYS */;
INSERT INTO `document_types` VALUES ('-1','Возврат от покупателя','sell'),('-2','Возврат поставщику','buy'),('1','Расходный документ','sell'),('10','Расходная Доверенность','warrantout'),('11','Приходная Доверенность','warrantin'),('12','Расходный Договор','contractout'),('13','Приходный Договор','contractin'),('14','Письмо','letter'),('2','Приходный документ','buy'),('3','Акт Оказанных Услуг','serviceout'),('4','Акт Полученных Услуг','servicein');
/*!40000 ALTER TABLE `document_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_view_list`
--

DROP TABLE IF EXISTS `document_view_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_view_list` (
  `doc_view_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_id` int(10) unsigned NOT NULL,
  `view_num` varchar(20) NOT NULL,
  `tstamp` timestamp NULL DEFAULT NULL,
  `view_efield_values` json DEFAULT NULL,
  `view_type_id` int(10) unsigned NOT NULL,
  `html` text NOT NULL,
  `freezed` tinyint(4) NOT NULL DEFAULT '0',
  `view_role` varchar(45) DEFAULT NULL,
  `created_by` tinyint(4) DEFAULT NULL,
  `modified_by` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_view_id`),
  UNIQUE KEY `Index_4` (`doc_id`,`view_type_id`),
  KEY `FK_document_views_2` (`view_type_id`),
  CONSTRAINT `FK_document_views_1` FOREIGN KEY (`doc_id`) REFERENCES `document_list` (`doc_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_document_views_2` FOREIGN KEY (`view_type_id`) REFERENCES `document_view_types` (`view_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_view_list`
--

LOCK TABLES `document_view_list` WRITE;
/*!40000 ALTER TABLE `document_view_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_view_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_view_types`
--

DROP TABLE IF EXISTS `document_view_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_view_types` (
  `view_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_types` varchar(20) NOT NULL,
  `blank_set` varchar(2) DEFAULT NULL,
  `view_name` varchar(45) NOT NULL,
  `view_role` varchar(45) NOT NULL,
  `view_efield_labels` text NOT NULL,
  `view_tpl` text CHARACTER SET latin1 NOT NULL,
  `view_file` text NOT NULL,
  `view_hidden` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`view_type_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_view_types`
--

LOCK TABLES `document_view_types` WRITE;
/*!40000 ALTER TABLE `document_view_types` DISABLE KEYS */;
INSERT INTO `document_view_types` VALUES (3,'/2/','ua','Замовлення','','','ua/doc/zamovlennya.xlsx','',NULL),(5,'/10/','ua','Довiренiсть','','','','html_form/dover.html',NULL),(6,'/11/','ua','Входящая Доверенность','','','','',NULL),(7,'/12/','ua','Договор поставки предоплата','sell_agreement','','','html_form/dogov_post_predopl.html',NULL),(8,'/12/','ua','Договор поставки отсрочка','sell_agreement','','','html_form/dogov_post_otsr.html',NULL),(9,'/13/','ua','Приходный договор','','','','',NULL),(11,'/12/','ua','Договор поставки реализация','sell_agreement','','','',NULL),(12,'/12/','ua','Договор аренды стенда','','','','',NULL),(22,'/1/3/','ua','Рахунок-фактура','bill','','ua/doc/rakhunok_faktura.html,ua/doc/rakhunok_faktura.xlsx','',NULL),(23,'/1/','ua','Видаткова Накладна','','{\"warrant\":\"Доверенность\",\"sell_condition\":\"Условие поставки\"}','ua/doc/vydatkova_nakladna.html,ua/doc/vydatkova_nakladna.xlsx','',NULL),(24,'/12/','ua','Договор Дистрибьюции','sell_agreement','','','html_form/dogov_distribution.html',NULL),(28,'/14/','ua','Лист про повернення коштів','','','','html_form/list_povern_koshtiv.html',NULL),(29,'/1/','ua','Товарно-Транспортна Накладна','','{\"vehicle\":\"Автомобіль\",\"vehicle2\":\"Номер причіпа\",\"del_comp\":\"Перевізник\",\"del_driver\":\"Водій\",\"place_number\":\"Місць цифрами\",\"place_number2\":\"Місць словами\",\"weight\":\"Вага словами\"}','ua/doc/TTN_2014.xlsx','',1),(31,'/3/','ua','Акт выполенных работ','','','ua/doc/service_invoice.xlsx','',NULL),(32,'/4/','ua','Акт выполенных работ (Вхідний)','','','ua/doc/service_invoice.xlsx','',NULL),(33,'/2/4/','ua','Податкова Накладна (Вхідна)','tax_bill','{\"sign\":\"Выписал\",\"type_of_reason\":\"Тип причины\"}','ua/doc/podatkova_nakladna2015_1.html','',NULL),(34,'/1/3/','ua','Податкова Накладна 2016','tax_bill','{\"sign\":\"Выписал\",\"type_of_reason\":\"Тип причины\"}','ua/doc/podatkova_nakladna2016.html,ua/doc/podatkova_nakladna2016.xml','',NULL),(103,'/2/','ru','Заказ','','','ru/doc/zakaz.xlsx','',NULL),(132,'/1/','ru','Товарный чек','','','ru/doc/tovarniy_check.html,ru/doc/tovarniy_check.xlsx','',NULL),(133,'/1/2/','ru','Торг 12','','{\r 	\"reciever\":{\"label\":\"Грузополучатель\",\"type\":\"company_id\"},\r 	\"place_count\":\"Всего мест\",\r                 \"supplier\":{\"label\":\"Грузоотправитель\",\"type\":\"company_id\"},\r                 \"reason\":\"Основание\",\r 	\"transport_bill_num\":\"ТТН №\",\r 	\"reason_num\":\"Основание №\",\r                 \"transport_bill_date\":{\"label\":\"ТТН дата\",\"type\":\"date\"},\r 	\"reason_date\":{\"label\":\"Основание дата\",\"type\":\"date\"}\r } ','ru/doc/torg12.html,ru/doc/torg12.xlsx','',NULL),(134,'/1/','ru','Накладная','','','ru/doc/nakladnaya.xlsx','',NULL),(136,'/1/','ru','Счет','','','ru/doc/schet.xlsx','',NULL),(137,'/3/','ru','Акт выполенных работ','','','ru/doc/act.xlsx','',NULL),(140,'/1/2/3/4/','ru','Счет фактура','tax_bill','{\r 	\"reciever\":{\"label\":\"Грузополучатель\",\"type\":\"company_id\"},\r                 \"supplier\":{\"label\":\"Грузоотправитель\",\"type\":\"company_id\"},\r     \"tax_date\":{\"label\":\"Дата постановки на учёт\",\"type\":\"date\"},\r                 \"reason\":\"Основание\"\r }','ru/doc/schet-faktura.html,ru/doc/schet-faktura.xlsx','',NULL),(141,'/1/2/','ru','УПД','tax_bill','{\n	\"reciever\":{\"label\":\"Грузополучатель\",\"type\":\"company_id\"},\n	\"place_count\":\"Всего мест\",\n                \"supplier\":{\"label\":\"Грузоотправитель\",\"type\":\"company_id\"},\n                \"reason\":\"Основание\",\n	\"transport_bill_num\":\"ТТН №\",\n	\"reason_num\":\"Основание №\",\n                \"transport_bill_date\":{\"label\":\"ТТН дата\",\"type\":\"date\"},\n	\"reason_date\":{\"label\":\"Основание дата\",\"type\":\"date\"}\n}','ru/doc/upd.xlsx','',NULL),(144,'/1/2/','ru','ТТН','sell_bill','{    \"reciever\": {        \"label\": \"Грузополучатель\",        \"type\": \"company_id\"    },    \"supplier\": {        \"label\": \"Грузоотправитель\",        \"type\": \"company_id\"    },    \"product_transport_bill_num\": \"Номер договора\",    \"product_transport_bill_date\": {        \"label\": \"Дата договора\",        \"type\": \"date\"    },    \"car_mark\": \"Марка автомобиля\",    \"car_num\": \"Номерной знак\",    \"driver_name\": \"ФИО Водителя\",    \"license_num\": \"Номер удостоверения\",    \"supplied_from\": \"Пункт погрузки\",    \"supplied_to\": \"Пункт разгрузки\",    \"delıvery_date\": {        \"label\": \"Дата доставки\",        \"type\": \"date\"    }} ','ru/doc/ttd.xlsx','',NULL);
/*!40000 ALTER TABLE `document_view_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_list`
--

DROP TABLE IF EXISTS `event_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_list` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_is_private` tinyint(1) NOT NULL,
  `event_user_id` int(11) DEFAULT NULL,
  `event_user_liable` varchar(45) DEFAULT NULL,
  `event_status` varchar(20) NOT NULL,
  `event_priority` varchar(45) DEFAULT NULL,
  `event_label` varchar(45) NOT NULL,
  `event_date` datetime DEFAULT NULL,
  `event_date_done` datetime DEFAULT NULL,
  `event_date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `event_repeat` varchar(45) DEFAULT NULL,
  `event_name` varchar(45) NOT NULL,
  `event_target` varchar(255) NOT NULL,
  `event_place` varchar(255) NOT NULL,
  `event_note` varchar(255) NOT NULL,
  `event_descr` text NOT NULL,
  `event_program` text,
  `created_by` tinyint(4) DEFAULT NULL,
  `modified_by` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `userid` (`event_user_id`),
  KEY `label` (`event_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_list`
--

LOCK TABLES `event_list` WRITE;
/*!40000 ALTER TABLE `event_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imported_data`
--

DROP TABLE IF EXISTS `imported_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imported_data` (
  `row_id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(45) NOT NULL,
  `A` text NOT NULL,
  `B` text NOT NULL,
  `C` text NOT NULL,
  `D` text NOT NULL,
  `E` text NOT NULL,
  `F` text NOT NULL,
  `G` text NOT NULL,
  `H` text NOT NULL,
  `I` text NOT NULL,
  `J` text NOT NULL,
  `K` text NOT NULL,
  `L` text NOT NULL,
  `M` text NOT NULL,
  `N` text NOT NULL,
  `O` text NOT NULL,
  `P` text NOT NULL,
  `Q` text NOT NULL,
  PRIMARY KEY (`row_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imported_data`
--

LOCK TABLES `imported_data` WRITE;
/*!40000 ALTER TABLE `imported_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `imported_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_list`
--

DROP TABLE IF EXISTS `log_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_list` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `cstamp` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата',
  `url` varchar(65) DEFAULT NULL COMMENT 'Адрес запроса',
  `log_class` varchar(45) DEFAULT NULL COMMENT 'Категория',
  `message` varchar(1000) DEFAULT NULL COMMENT 'Сообщение ',
  PRIMARY KEY (`entry_id`),
  KEY `tstamp` (`cstamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_list`
--

LOCK TABLES `log_list` WRITE;
/*!40000 ALTER TABLE `log_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_list`
--

DROP TABLE IF EXISTS `plugin_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_list` (
  `plugin_system_name` varchar(45) NOT NULL,
  `plugin_settings` text,
  `plugin_json_data` json DEFAULT NULL,
  `trigger_before` text,
  `trigger_after` text,
  `is_installed` tinyint(4) DEFAULT NULL,
  `is_activated` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`plugin_system_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_list`
--

LOCK TABLES `plugin_list` WRITE;
/*!40000 ALTER TABLE `plugin_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pref_list`
--

DROP TABLE IF EXISTS `pref_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pref_list` (
  `active_company_id` int(10) unsigned NOT NULL,
  `pref_name` varchar(45) NOT NULL,
  `pref_value` text NOT NULL,
  `pref_int` int(11) NOT NULL,
  PRIMARY KEY (`active_company_id`,`pref_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pref_list`
--

LOCK TABLES `pref_list` WRITE;
/*!40000 ALTER TABLE `pref_list` DISABLE KEYS */;
INSERT INTO `pref_list` VALUES (0,'db_applied_patches','|2016-09-01-0000|2016-09-25-1527|2016-09-25-1528|2016-09-26-2147|2016-11-13-1311|2016-11-17-2138|2016-11-24-2139|2016-11-24-2150|2016-11-26-1153|2016-11-27-2202|2016-12-03-1457|2016-12-07-0842|2016-12-07-2115|2016-12-17-1456|2016-12-24-1550|2016-12-27-2210|2016-12-29-2134|2017-01-06-2220|2017-01-06-2222|2017-02-24-2122|2017-05-18-1547|2017-05-20-2222|2017-10-06-1815|2017-10-06-1820|2017-10-24-2205|2017-10-28-1605|2017-11-09-1614|2017-11-09-1624|2017-11-09-1631|2018-01-08-1424|2018-01-08-2243|2018-01-15-1408|2018-02-09-1747|2018-02-12-1718|2018-02-15-1151|2018-03-08-1738|2018-03-21-1427|2018-04-10-1410|2018-05-08-1708|2018-05-10-1102|2018-06-13-1426|2018-07-17-2100|2018-07-17-2105|2018-08-31-1859|2018-09-05-1142|2018-09-06-1442|2018-09-11-1959|2018-09-11-2053|2018-09-15-1448|2018-09-17-1951|2018-09-29-1632|2018-10-03-1321|2018-10-04-1518|2018-10-09-1042|2018-10-26-1655|2018-10-30-1539|2018-11-01-1112|2018-12-05-1037|2019-01-05-1752|2019-02-27-1300|2019-03-02-1049|2019-03-02-1253|2019-03-07-1001|2019-03-07-1228|2019-03-21-1656|2019-05-09-1223breakeven|2019-05-15-1233breakevenFunction|2019-05-15-1236|2019-05-15-1411breakevenCheckEntry|2019-05-16-1147|2019-05-16-1514leftovercalcFix|2019-05-22-1653breakevenPromoPrice|2019-06-21-1300|2019-06-21-1724|2019-06-22-1249|2019-07-01-1619|2019-07-01-1851|2019-07-30-1333|2019-08-03-11-29|2019-08-03-15-37|2019-08-15-1545|2019-09-05-1444|2019-11-22-1146|2019-11-23-1512|2019-11-29-1657|2019-11-30-1557|2019-12-07-1453-act|2019-12-17-1001|2019-12-26-1236-auto_created_modified|2019-12-28-1029-auto_created_modified|2019-12-28-1122|2020-02-25-1836|2020-02-27-1041|2020-02-29-1613|2020-03-05-1417|2020-03-14-1147|2020-03-21-1329|2020-03-28-1425|2020-04-02-1201-stock-entries|2020-04-16-1420|2020-04-16-1601|2020-05-15-1757|2020-06-01-1047|2020-06-09-1107|2020-06-09-1108|2020-06-09-1208|2020-07-23-1536|2020-07-23-1626|2020-07-25-1202|2020-07-25-1220|2020-10-17-1450|2020-11-10-1117|2020-11-16-1605|2020-11-21-1348|2020-12-25-1623|2021-01-12-1725|2021-02-24-1637',0),(1,'blank_set','ru',0),(1,'counterDocNum_1','null',1);
/*!40000 ALTER TABLE `pref_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_breakeven`
--

DROP TABLE IF EXISTS `price_breakeven`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_breakeven` (
  `breakeven_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `breakeven_ratio` float DEFAULT NULL,
  `breakeven_base` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`breakeven_rule_id`),
  UNIQUE KEY `index2` (`company_id`,`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_breakeven`
--

LOCK TABLES `price_breakeven` WRITE;
/*!40000 ALTER TABLE `price_breakeven` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_breakeven` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_list`
--

DROP TABLE IF EXISTS `price_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_list` (
  `product_code` varchar(45) NOT NULL COMMENT 'Код товара',
  `sell` double NOT NULL COMMENT 'Продажа',
  `buy` double NOT NULL COMMENT 'Покупка',
  `curr_code` varchar(45) NOT NULL COMMENT 'Код валюты',
  `label` varchar(45) NOT NULL COMMENT 'Категория цен',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_code`,`label`) USING BTREE,
  KEY `FK_price_list_1` (`product_code`),
  CONSTRAINT `FK_prodcode` FOREIGN KEY (`product_code`) REFERENCES `prod_list` (`product_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_list`
--

LOCK TABLES `price_list` WRITE;
/*!40000 ALTER TABLE `price_list` DISABLE KEYS */;
INSERT INTO `price_list` VALUES ('A001',1,0.5,'','','2021-04-13 12:12:47','2021-04-13 12:12:47');
/*!40000 ALTER TABLE `price_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prod_list`
--

DROP TABLE IF EXISTS `prod_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prod_list` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(45) NOT NULL COMMENT 'Код товара',
  `product_article` varchar(45) DEFAULT NULL COMMENT 'Артикул',
  `ru` varchar(255) NOT NULL COMMENT 'Название Рус.',
  `ua` varchar(255) NOT NULL COMMENT 'Назва Укр.',
  `en` varchar(255) NOT NULL COMMENT 'Name En.',
  `product_barcode` varchar(13) NOT NULL COMMENT 'Штрихкод',
  `product_bpack` int(10) unsigned NOT NULL COMMENT 'Бол. упак.',
  `product_spack` int(10) unsigned NOT NULL COMMENT 'Мал. упак.',
  `product_weight` double NOT NULL COMMENT 'Вес ед.',
  `product_volume` double NOT NULL COMMENT 'Объем ед.',
  `product_unit` varchar(5) NOT NULL COMMENT 'Единица',
  `is_service` tinyint(3) unsigned NOT NULL COMMENT 'Услуга?',
  `analyse_type` varchar(45) NOT NULL COMMENT 'Тип',
  `analyse_brand` varchar(45) NOT NULL COMMENT 'Бренд',
  `analyse_class` varchar(45) NOT NULL COMMENT 'Класс',
  `analyse_origin` varchar(45) NOT NULL COMMENT 'Таможенный код',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `index2` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prod_list`
--

LOCK TABLES `prod_list` WRITE;
/*!40000 ALTER TABLE `prod_list` DISABLE KEYS */;
INSERT INTO `prod_list` VALUES (1,'A001','','Товар','','','',0,0,0,0,'шт',0,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(2,'аренда',NULL,'Аренда помешений','Оренда приміщень','Rent for building','',0,0,0,0,'м2',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(3,'интернет',NULL,'Интернет','Інтернет','Internet','',0,0,0,0,'мес',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(4,'канц',NULL,'Канц. товары','Канц. товари','Stationery','',0,0,0,0,'шт',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(5,'офис',NULL,'Материалы для офиса','Матеріали для офісу','Items for office','',0,0,0,0,'шт',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(6,'ремонт',NULL,'Ремонт авто или помещений','Ремонт авто чи приміщень','Repair of vehicle or office','',0,0,0,0,'шт',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(7,'телефон',NULL,'Телефонная связь','Телефонний з`вязок','Phone','',0,0,0,0,'мес',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(8,'топливо',NULL,'Топливо','Паливо','Fuel','',0,0,0,0,'л',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(9,'услуга',NULL,'Услуга','Послуга','Service','',0,0,0,0,'шт',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47'),(10,'эл-во',NULL,'Электроэнергия','Електроенергія','Electricity','',0,0,0,0,'кВт*ч',1,'','','','','2021-04-13 12:12:47','2021-04-13 12:12:47');
/*!40000 ALTER TABLE `prod_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_entries`
--

DROP TABLE IF EXISTS `stock_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_entries` (
  `stock_entry_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `stock_id` int(11) NOT NULL DEFAULT '1',
  `product_code` varchar(45) NOT NULL,
  `product_quantity` double NOT NULL,
  `product_reserve` double DEFAULT NULL,
  `product_awaiting` double DEFAULT NULL,
  `product_wrn_quantity` int(10) unsigned NOT NULL,
  `product_img` varchar(45) DEFAULT NULL,
  `fetch_count` int(11) NOT NULL,
  `fetch_stamp` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `party_label` varchar(45) DEFAULT NULL COMMENT 'DEPRECATED',
  `vat_quantity` int(10) NOT NULL COMMENT 'DEPRECATED',
  `self_price` double NOT NULL COMMENT 'DEPRECATED',
  PRIMARY KEY (`stock_entry_id`),
  UNIQUE KEY `index4` (`product_code`,`stock_id`),
  KEY `fetch_count_index` (`fetch_count`),
  KEY `Index_3` (`product_code`),
  CONSTRAINT `FK_stock_entries_1` FOREIGN KEY (`product_code`) REFERENCES `prod_list` (`product_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5524 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_entries`
--

LOCK TABLES `stock_entries` WRITE;
/*!40000 ALTER TABLE `stock_entries` DISABLE KEYS */;
INSERT INTO `stock_entries` VALUES (5514,1,1,'A001',0,NULL,NULL,0,NULL,3,'2016-04-10 12:09:28','2021-04-13 12:12:47','2021-04-13 12:12:47','',0,0.5),(5515,2,1,'аренда',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5516,2,1,'топливо',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5517,2,1,'интернет',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5518,2,1,'эл-во',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5519,2,1,'канц',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5520,2,1,'офис',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5521,2,1,'телефон',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5522,2,1,'ремонт',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0),(5523,2,1,'услуга',0,NULL,NULL,0,NULL,0,NULL,'2021-04-13 12:12:47','2021-04-13 12:12:47',NULL,0,0);
/*!40000 ALTER TABLE `stock_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_tree`
--

DROP TABLE IF EXISTS `stock_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_tree` (
  `branch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `label` varchar(45) NOT NULL,
  `is_leaf` tinyint(1) NOT NULL,
  `branch_data` text NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `path` text,
  `top_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_tree`
--

LOCK TABLES `stock_tree` WRITE;
/*!40000 ALTER TABLE `stock_tree` DISABLE KEYS */;
INSERT INTO `stock_tree` VALUES (1,0,'Категория',0,'',0,NULL,0),(2,0,'Услуги',0,'',0,'/Услуги/',2);
/*!40000 ALTER TABLE `stock_tree` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_list`
--

DROP TABLE IF EXISTS `user_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_list` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_login` varchar(45) NOT NULL,
  `user_pass` varchar(45) DEFAULT NULL,
  `user_level` int(11) DEFAULT NULL,
  `user_sign` varchar(45) DEFAULT NULL,
  `user_position` varchar(255) DEFAULT NULL,
  `user_phone` varchar(45) DEFAULT NULL,
  `user_email` varchar(45) DEFAULT NULL,
  `user_is_staff` tinyint(4) DEFAULT '0',
  `user_tax_id` varchar(45) DEFAULT NULL,
  `first_name` varchar(45) DEFAULT NULL,
  `middle_name` varchar(45) DEFAULT NULL,
  `last_name` varchar(45) DEFAULT NULL,
  `nick` varchar(45) DEFAULT NULL,
  `id_type` varchar(45) DEFAULT NULL,
  `id_serial` varchar(45) DEFAULT NULL,
  `id_number` varchar(45) DEFAULT NULL,
  `id_given_by` text,
  `id_date` varchar(45) DEFAULT NULL,
  `company_id` int(11) DEFAULT '1',
  `user_permissions` text,
  `user_assigned_path` text,
  `user_assigned_stat` text,
  `last_activity` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_list`
--

LOCK TABLES `user_list` WRITE;
/*!40000 ALTER TABLE `user_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `client_list`
--

/*!50001 DROP VIEW IF EXISTS `client_list`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `client_list` AS select `companies_tree`.`label` AS `label`,`companies_list`.`company_name` AS `company_name`,`companies_tree`.`path` AS `path`,`companies_list`.`company_person` AS `company_person`,`companies_list`.`company_mobile` AS `company_mobile`,`companies_list`.`company_email` AS `company_email`,`companies_list`.`company_web` AS `company_web`,`companies_list`.`company_address` AS `company_address`,`companies_list`.`company_jaddress` AS `company_jaddress`,`companies_list`.`company_director` AS `company_director`,`companies_list`.`company_description` AS `company_description` from (`companies_list` join `companies_tree` on((`companies_list`.`branch_id` = `companies_tree`.`branch_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-04-13 15:14:52

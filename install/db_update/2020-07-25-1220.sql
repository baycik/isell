UPDATE `isell_db`.`document_view_types` SET `view_name` = 'ТТН' WHERE (`view_type_id` = '144');
UPDATE `isell_db`.`document_view_types` SET `view_efield_labels` = '{    \"reciever\": {        \"label\": \"Грузополучатель\",        \"type\": \"company_id\"    },    \"supplier\": {        \"label\": \"Грузоотправитель\",        \"type\": \"company_id\"    },    \"product_transport_bill_num\": \"Номер договора\",    \"product_transport_bill_date\": {        \"label\": \"Дата договора\",        \"type\": \"date\"    },    \"car_mark\": \"Марка автомобиля\",    \"car_num\": \"Номерной знак\",    \"driver_name\": \"ФИО Водителя\",    \"license_num\": \"Номер удостоверения\",    \"supplied_from\": \"Пункт погрузки\",    \"supplied_to\": \"Пункт разгрузки\",    \"delıvery_date\": {        \"label\": \"Дата доставки\",        \"type\": \"date\"    }} ' WHERE (`view_type_id` = '144');
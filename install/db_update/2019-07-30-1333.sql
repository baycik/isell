ALTER TABLE `event_list` 
ADD INDEX `event_name_ind` (`event_name` ASC)
ADD INDEX `event_label_ind` (`event_label` ASC);

TRUNCATE document_entries;
TRUNCATE document_list;
TRUNCATE document_trans;
TRUNCATE event_list;
TRUNCATE acc_trans;
TRUNCATE plugin_list;
TRUNCATE log_list;
DELETE FROM user_list;
DELETE FROM pref_list WHERE pref_name<>'db_applied_patches'
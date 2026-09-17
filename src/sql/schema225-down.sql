-- revert schema 225
DELETE FROM `config` WHERE conf_name = 'mail_from_name';
DELETE FROM `config` WHERE conf_name = 'mail_subject_prefix';


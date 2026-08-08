-- load a sql dump (/in.sql) locally
drop database elabftw; create database elabftw character set utf8mb4 collate utf8mb4_0900_ai_ci; use elabftw; source /in.sql;
update config set conf_value = '' where conf_name = 'smtp_password';
update config set conf_value = 'nope.example.com' where conf_name = 'smtp_address';
update config set conf_value = '' where conf_name = 'ts_password';

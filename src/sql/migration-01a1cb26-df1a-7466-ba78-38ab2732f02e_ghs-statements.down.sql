-- revert migration 01a1cb26-df1a-7466-ba78-38ab2732f02e

DROP TABLE IF EXISTS `compounds2ghs_statements`;
DROP TABLE IF EXISTS `ghs_statements`;
CALL DropColumn('compounds', 'ghs_signal_word');

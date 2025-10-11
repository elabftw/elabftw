-- schema 186
ALTER TABLE `compounds`
    CHANGE `name` `name` TEXT NULL DEFAULT NULL,
    ADD INDEX idx_compounds_name (name(255));

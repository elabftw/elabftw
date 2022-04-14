-- Schema 84
-- improve primary key for links
START TRANSACTION;
    -- experiments_links remove duplicates, triplicates, ...
    DELETE FROM experiments_links
    WHERE id IN (
      SELECT id FROM (
        SELECT MIN(t1.id) as id
        FROM experiments_links AS t1
        INNER JOIN experiments_links AS t2
          ON (t1.item_id = t2.item_id
            AND t1.link_id = t2.link_id
            AND t1.id < t2.id
          )
        GROUP BY t1.id
      ) tmp
    );
    ALTER TABLE `experiments_links` DROP `id`;
    ALTER TABLE `experiments_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    -- experiments_templates_links remove duplicates, triplicates, ...
    DELETE FROM experiments_templates_links
    WHERE id IN (
      SELECT id FROM (
        SELECT MIN(t1.id) as id
        FROM experiments_templates_links AS t1
        INNER JOIN experiments_templates_links AS t2
          ON (t1.item_id = t2.item_id
            AND t1.link_id = t2.link_id
            AND t1.id < t2.id
          )
        GROUP BY t1.id
      ) tmp
    );
    ALTER TABLE `experiments_templates_links` DROP `id`;
    ALTER TABLE `experiments_templates_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    -- items_links remove duplicates, triplicates, ...
    DELETE FROM items_links
    WHERE id IN (
      SELECT id FROM (
        SELECT MIN(t1.id) as id
        FROM items_links AS t1
        INNER JOIN items_links AS t2
          ON (t1.item_id = t2.item_id
            AND t1.link_id = t2.link_id
            AND t1.id < t2.id
          )
        GROUP BY t1.id
      ) tmp
    );
    ALTER TABLE `items_links` DROP `id`;
    ALTER TABLE `items_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    -- items_types_links remove duplicates, triplicates, ...
    DELETE FROM items_types_links
    WHERE id IN (
      SELECT id FROM (
        SELECT MIN(t1.id) as id
        FROM items_types_links AS t1
        INNER JOIN items_types_links AS t2
          ON (t1.item_id = t2.item_id
            AND t1.link_id = t2.link_id
            AND t1.id < t2.id
          )
        GROUP BY t1.id
      ) tmp
    );
    ALTER TABLE `items_types_links` DROP `id`;
    ALTER TABLE `items_types_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    UPDATE config SET conf_value = 84 WHERE conf_name = 'schema';
COMMIT;

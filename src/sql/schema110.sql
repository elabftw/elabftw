-- schema 110
ALTER TABLE `tags2entity` DROP `id`;
ALTER TABLE `tags2entity`
  ADD PRIMARY KEY (`item_id`, `tag_id`, `item_type`);
ALTER TABLE `tags2entity`
  ADD CONSTRAINT `fk_tags2entity_tags_id` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE config SET conf_value = 110 WHERE conf_name = 'schema';

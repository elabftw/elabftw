-- schema 224
ALTER TABLE `tags2entity`
    ADD INDEX `idx_tags2entity_tag_type_item` (`tag_id`, `item_type`, `item_id`);

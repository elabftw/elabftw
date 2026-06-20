-- schema 213
ALTER TABLE `experiments`
    ADD COLUMN `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN `last_signed_by` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `experiments_templates`
    ADD COLUMN `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN `last_signed_by` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `items`
    ADD COLUMN `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN `last_signed_by` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `items_types`
    ADD COLUMN `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN `last_signed_by` INT UNSIGNED NULL DEFAULT NULL;

-- Backfill signature helper columns from existing signature archives.
-- A signature is represented by an upload with real_name = 'signature-archive.zip'.
-- In case of identical created_at values, use the highest uploads.id as a deterministic tie breaker.

UPDATE `experiments` AS entity
JOIN (
    SELECT
        `uploads`.`item_id`,
        COUNT(*) AS `signature_count`,
        MAX(`uploads`.`created_at`) AS `last_signed_at`,
        CAST(SUBSTRING_INDEX(GROUP_CONCAT(`uploads`.`userid` ORDER BY `uploads`.`created_at` DESC, `uploads`.`id` DESC), ',', 1) AS UNSIGNED) AS `last_signed_by`
    FROM `uploads`
    WHERE `uploads`.`type` = 'experiments'
        AND `uploads`.`real_name` = 'signature-archive.zip'
    GROUP BY `uploads`.`item_id`
) AS signatures ON signatures.`item_id` = entity.`id`
SET
    entity.`signature_count` = signatures.`signature_count`,
    entity.`last_signed_at` = signatures.`last_signed_at`,
    entity.`last_signed_by` = signatures.`last_signed_by`;

UPDATE `experiments_templates` AS entity
JOIN (
    SELECT
        `uploads`.`item_id`,
        COUNT(*) AS `signature_count`,
        MAX(`uploads`.`created_at`) AS `last_signed_at`,
        CAST(SUBSTRING_INDEX(GROUP_CONCAT(`uploads`.`userid` ORDER BY `uploads`.`created_at` DESC, `uploads`.`id` DESC), ',', 1) AS UNSIGNED) AS `last_signed_by`
    FROM `uploads`
    WHERE `uploads`.`type` = 'experiments_templates'
        AND `uploads`.`real_name` = 'signature-archive.zip'
    GROUP BY `uploads`.`item_id`
) AS signatures ON signatures.`item_id` = entity.`id`
SET
    entity.`signature_count` = signatures.`signature_count`,
    entity.`last_signed_at` = signatures.`last_signed_at`,
    entity.`last_signed_by` = signatures.`last_signed_by`;

UPDATE `items` AS entity
JOIN (
    SELECT
        `uploads`.`item_id`,
        COUNT(*) AS `signature_count`,
        MAX(`uploads`.`created_at`) AS `last_signed_at`,
        CAST(SUBSTRING_INDEX(GROUP_CONCAT(`uploads`.`userid` ORDER BY `uploads`.`created_at` DESC, `uploads`.`id` DESC), ',', 1) AS UNSIGNED) AS `last_signed_by`
    FROM `uploads`
    WHERE `uploads`.`type` = 'items'
        AND `uploads`.`real_name` = 'signature-archive.zip'
    GROUP BY `uploads`.`item_id`
) AS signatures ON signatures.`item_id` = entity.`id`
SET
    entity.`signature_count` = signatures.`signature_count`,
    entity.`last_signed_at` = signatures.`last_signed_at`,
    entity.`last_signed_by` = signatures.`last_signed_by`;

UPDATE `items_types` AS entity
JOIN (
    SELECT
        `uploads`.`item_id`,
        COUNT(*) AS `signature_count`,
        MAX(`uploads`.`created_at`) AS `last_signed_at`,
        CAST(SUBSTRING_INDEX(GROUP_CONCAT(`uploads`.`userid` ORDER BY `uploads`.`created_at` DESC, `uploads`.`id` DESC), ',', 1) AS UNSIGNED) AS `last_signed_by`
    FROM `uploads`
    WHERE `uploads`.`type` = 'items_types'
        AND `uploads`.`real_name` = 'signature-archive.zip'
    GROUP BY `uploads`.`item_id`
) AS signatures ON signatures.`item_id` = entity.`id`
SET
    entity.`signature_count` = signatures.`signature_count`,
    entity.`last_signed_at` = signatures.`last_signed_at`,
    entity.`last_signed_by` = signatures.`last_signed_by`;

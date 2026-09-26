-- schema225
ALTER TABLE `experiments_categories`
  ADD COLUMN `color_fg` CHAR(6) NOT NULL DEFAULT 'ffffff' AFTER `color`;

ALTER TABLE `items_categories`
  ADD COLUMN `color_fg` CHAR(6) NOT NULL DEFAULT 'ffffff' AFTER `color`;

-- for existing. initialize the foreground intelligently instead of making everything white
UPDATE `experiments_categories`
SET `color_fg` = CASE
  WHEN (
    CONV(SUBSTRING(`color`, 1, 2), 16, 10) * 299 +
    CONV(SUBSTRING(`color`, 3, 2), 16, 10) * 587 +
    CONV(SUBSTRING(`color`, 5, 2), 16, 10) * 114
  ) / 1000 >= 128
  THEN '000000'
  ELSE 'ffffff'
END;

UPDATE `items_categories`
SET `color_fg` = CASE
  WHEN (
    CONV(SUBSTRING(`color`, 1, 2), 16, 10) * 299 +
    CONV(SUBSTRING(`color`, 3, 2), 16, 10) * 587 +
    CONV(SUBSTRING(`color`, 5, 2), 16, 10) * 114
  ) / 1000 >= 128
  THEN '000000'
  ELSE 'ffffff'
END;

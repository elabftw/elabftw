-- schema 147 - drop cjk because we use Noto Font and substitutions
ALTER TABLE `users` DROP COLUMN `cjk_fonts`;
UPDATE config SET conf_value = 147 WHERE conf_name = 'schema';

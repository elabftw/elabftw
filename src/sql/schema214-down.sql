-- revert schema 214
INSERT INTO config (conf_name, conf_value)
SELECT
    branding_type.conf_name,
    CONVERT(branding.data USING utf8mb4)
FROM branding
JOIN (
    SELECT 1 AS id, 'logo_header_svg' AS conf_name
    UNION ALL SELECT 2, 'logo_light_svg'
    UNION ALL SELECT 3, 'logo_dark_svg'
    UNION ALL SELECT 4, 'favicon_svg'
) AS branding_type ON branding_type.id = branding.id
ON DUPLICATE KEY UPDATE
    conf_value = VALUES(conf_value);

DROP TABLE IF EXISTS branding;

UPDATE config SET conf_value = 213 WHERE conf_name = 'schema';

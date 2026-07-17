-- schema 214
CREATE TABLE IF NOT EXISTS branding (
    id TINYINT UNSIGNED NOT NULL,
    content_type VARCHAR(127) NOT NULL,
    data MEDIUMBLOB NOT NULL,
    filesize INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
INSERT INTO branding (id, content_type, data, filesize)
SELECT
    branding_type.id,
    'image/svg+xml',
    CAST(config.conf_value AS BINARY),
    OCTET_LENGTH(config.conf_value)
FROM config
JOIN (
    SELECT 1 AS id, 'logo_header_svg' AS conf_name
    UNION ALL SELECT 2, 'logo_light_svg'
    UNION ALL SELECT 3, 'logo_dark_svg'
    UNION ALL SELECT 4, 'favicon_svg'
) AS branding_type ON branding_type.conf_name = config.conf_name
WHERE config.conf_value IS NOT NULL
  AND config.conf_value != ''
ON DUPLICATE KEY UPDATE
    content_type = VALUES(content_type),
    data = VALUES(data),
    filesize = VALUES(filesize),
    modified_at = CURRENT_TIMESTAMP;
DELETE FROM config WHERE conf_name IN ('logo_header_svg', 'logo_light_svg', 'logo_dark_svg', 'favicon_svg');

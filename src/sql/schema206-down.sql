-- revert schema 206
DELETE FROM config WHERE conf_name = 'logo_header_svg';
DELETE FROM config WHERE conf_name = 'logo_light_svg';
DELETE FROM config WHERE conf_name = 'logo_dark_svg';
DELETE FROM config WHERE conf_name = 'favicon_svg';
UPDATE config SET conf_value = 205 WHERE conf_name = 'schema';

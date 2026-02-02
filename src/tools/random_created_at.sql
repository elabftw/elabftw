-- run this to have created_at be randomized for experiments, items and users tables.

WITH rnd AS (
  SELECT
    id,
    RAND()  AS r_bucket,
    RAND()  AS r_day,
    RAND()  AS r_sec
  FROM experiments
)
UPDATE experiments e
JOIN rnd ON rnd.id = e.id
SET e.created_at =
  (
    CASE
      WHEN rnd.r_bucket < 0.50 THEN
        NOW() - INTERVAL FLOOR(rnd.r_day * 182) DAY

      WHEN rnd.r_bucket < 0.80 THEN
        NOW() - INTERVAL (182 + FLOOR(rnd.r_day * 548)) DAY

      ELSE
        NOW() - INTERVAL (730 + FLOOR(rnd.r_day * 1095)) DAY
    END
  )
  + INTERVAL FLOOR(rnd.r_sec * 86400) SECOND;

WITH rnd AS (
  SELECT
    id,
    RAND()  AS r_bucket,
    RAND()  AS r_day,
    RAND()  AS r_sec
  FROM items
)
UPDATE items e
JOIN rnd ON rnd.id = e.id
SET e.created_at =
  (
    CASE
      WHEN rnd.r_bucket < 0.50 THEN
        NOW() - INTERVAL FLOOR(rnd.r_day * 182) DAY

      WHEN rnd.r_bucket < 0.80 THEN
        NOW() - INTERVAL (182 + FLOOR(rnd.r_day * 548)) DAY

      ELSE
        NOW() - INTERVAL (730 + FLOOR(rnd.r_day * 1095)) DAY
    END
  )
  + INTERVAL FLOOR(rnd.r_sec * 86400) SECOND;
WITH rnd AS (
  SELECT
    id,
    RAND()  AS r_bucket,
    RAND()  AS r_day,
    RAND()  AS r_sec
  FROM items
)
UPDATE items e
JOIN rnd ON rnd.id = e.id
SET e.created_at =
  (
    CASE
      WHEN rnd.r_bucket < 0.50 THEN
        NOW() - INTERVAL FLOOR(rnd.r_day * 182) DAY

      WHEN rnd.r_bucket < 0.80 THEN
        NOW() - INTERVAL (182 + FLOOR(rnd.r_day * 548)) DAY

      ELSE
        NOW() - INTERVAL (730 + FLOOR(rnd.r_day * 1095)) DAY
    END
  )
  + INTERVAL FLOOR(rnd.r_sec * 86400) SECOND;
WITH rnd AS (
  SELECT
    userid,
    RAND()  AS r_bucket,
    RAND()  AS r_day,
    RAND()  AS r_sec
  FROM users
)
UPDATE users e
JOIN rnd ON rnd.userid = e.userid
SET e.created_at =
  (
    CASE
      WHEN rnd.r_bucket < 0.50 THEN
        NOW() - INTERVAL FLOOR(rnd.r_day * 182) DAY

      WHEN rnd.r_bucket < 0.80 THEN
        NOW() - INTERVAL (182 + FLOOR(rnd.r_day * 548)) DAY

      ELSE
        NOW() - INTERVAL (730 + FLOOR(rnd.r_day * 1095)) DAY
    END
  )
  + INTERVAL FLOOR(rnd.r_sec * 86400) SECOND;

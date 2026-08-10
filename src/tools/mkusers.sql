-- create 5000 users
SET @load_batch = CONCAT('lt', REPLACE(UUID(), '-', ''));

DROP TEMPORARY TABLE IF EXISTS tmp_team;
DROP TEMPORARY TABLE IF EXISTS tmp_user;

INSERT INTO users (
    firstname,
    lastname,
    email,
    validated,
    default_read,
    default_write
)
SELECT
    CONCAT('Loadtest ', number_value),
    'User',
    CONCAT(@load_batch, '.', number_value, '@example.invalid'),
    1,
    '{"teams": [], "teamgroups": [], "users": []}',
    '{"teams": [], "teamgroups": [], "users": []}'
FROM (
    SELECT
        digit_one.digit_value
        + digit_ten.digit_value * 10
        + digit_hundred.digit_value * 100
        + digit_thousand.digit_value * 1000
        + 1 AS number_value
    FROM (
        SELECT 0 AS digit_value
        UNION ALL SELECT 1
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
        UNION ALL SELECT 7
        UNION ALL SELECT 8
        UNION ALL SELECT 9
    ) AS digit_one
    CROSS JOIN (
        SELECT 0 AS digit_value
        UNION ALL SELECT 1
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
        UNION ALL SELECT 7
        UNION ALL SELECT 8
        UNION ALL SELECT 9
    ) AS digit_ten
    CROSS JOIN (
        SELECT 0 AS digit_value
        UNION ALL SELECT 1
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
        UNION ALL SELECT 7
        UNION ALL SELECT 8
        UNION ALL SELECT 9
    ) AS digit_hundred
    CROSS JOIN (
        SELECT 0 AS digit_value
        UNION ALL SELECT 1
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
        UNION ALL SELECT 7
        UNION ALL SELECT 8
        UNION ALL SELECT 9
    ) AS digit_thousand
) AS number_list
WHERE number_value <= 5000;

CREATE TEMPORARY TABLE tmp_team (
    team_id INT UNSIGNED NOT NULL,
    team_position INT UNSIGNED NOT NULL,
    PRIMARY KEY (team_position),
    UNIQUE KEY (team_id)
) ENGINE = MEMORY;

INSERT INTO tmp_team (
    team_id,
    team_position
)
SELECT
    id,
    ROW_NUMBER() OVER (ORDER BY RAND())
FROM teams;

SET @load_team_count = (
    SELECT COUNT(*)
    FROM tmp_team
);

CREATE TEMPORARY TABLE tmp_user (
    user_id INT UNSIGNED NOT NULL,
    desired_team_count TINYINT UNSIGNED NOT NULL,
    starting_team_position INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id)
) ENGINE = MEMORY;

INSERT INTO tmp_user (
    user_id,
    desired_team_count,
    starting_team_position
)
SELECT
    userid,
    1 + MOD(
        CRC32(CONCAT(@load_batch, ':count:', userid)),
        LEAST(5, @load_team_count)
    ),
    1 + MOD(
        CRC32(CONCAT(@load_batch, ':start:', userid)),
        @load_team_count
    )
FROM users
WHERE email LIKE CONCAT(@load_batch, '.%@example.invalid');

INSERT INTO users2teams (
    users_id,
    teams_id,
    is_owner,
    is_admin,
    is_archived
)
SELECT
    load_user.user_id,
    load_team.team_id,
    0,
    0,
    0
FROM tmp_user AS load_user
INNER JOIN (
    SELECT 1 AS slot_number
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
) AS slot_list
    ON slot_list.slot_number <= load_user.desired_team_count
INNER JOIN tmp_team AS load_team
    ON load_team.team_position = 1 + MOD(
        load_user.starting_team_position
        + slot_list.slot_number
        - 2,
        @load_team_count
    );

SELECT @load_batch AS load_batch;

SELECT
    COUNT(*) AS created_users,
    SUM(team_total) AS created_memberships,
    MIN(team_total) AS minimum_teams_per_user,
    MAX(team_total) AS maximum_teams_per_user,
    AVG(team_total) AS average_teams_per_user
FROM (
    SELECT
        load_account.userid,
        COUNT(load_membership.teams_id) AS team_total
    FROM users AS load_account
    INNER JOIN users2teams AS load_membership
        ON load_membership.users_id = load_account.userid
    WHERE load_account.email LIKE CONCAT(
        @load_batch,
        '.%@example.invalid'
    )
    GROUP BY load_account.userid
) AS load_result;

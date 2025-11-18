-- schema 192
CREATE TABLE IF NOT EXISTS `idps_certs`
(
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idp                 INT UNSIGNED NOT NULL,
    purpose             TINYINT UNSIGNED NOT NULL,
    x509                TEXT NOT NULL,
    sha256              CHAR(64) NOT NULL,
    not_before          DATETIME NULL,
    not_after           DATETIME NULL,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),

    CONSTRAINT fk_idps_certs_idp_id
        FOREIGN KEY (idp)
        REFERENCES idps(id)
        ON DELETE CASCADE,

    UNIQUE KEY uniq_idp_purpose_fpr (idp, purpose, sha256),
    KEY idx_idp_purpose_active (idp, purpose, is_active, not_before, not_after)
);
INSERT IGNORE INTO idps_certs (idp, purpose, x509)
(
SELECT  id       AS idp,
        0        AS purpose,
        x509     AS x509
FROM    idps
WHERE   x509 IS NOT NULL
  AND   x509 != ''
);
INSERT IGNORE INTO idps_certs (idp, purpose, x509)
(
SELECT  id         AS idp,
        0          AS purpose,
        x509_new   AS x509
FROM    idps
WHERE   x509_new IS NOT NULL
  AND   x509_new != ''
);

CALL DropColumn('idps', 'x509');
CALL DropColumn('idps', 'x509_new');

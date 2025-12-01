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
    is_active           TINYINT UNSIGNED NOT NULL DEFAULT 1,
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
INSERT IGNORE INTO idps_certs (idp, purpose, x509, sha256)
(
SELECT  id       AS idp,
        0        AS purpose,
        x509     AS x509,
        LEFT(
          CONCAT(
            'legacy_unfingerprinted_cert_',
            REPLACE(UUID(), '-', ''),
            REPLACE(UUID(), '-', '')
          ),
          64
        ) AS sha256
FROM    idps
WHERE   x509 IS NOT NULL
  AND   x509 != ''
);
INSERT IGNORE INTO idps_certs (idp, purpose, x509, sha256)
(
SELECT  id         AS idp,
        0          AS purpose,
        x509_new   AS x509,
        LEFT(
          CONCAT(
            'legacy_unfingerprinted_cert_',
            REPLACE(UUID(), '-', ''),
            REPLACE(UUID(), '-', '')
          ),
          64
        ) AS sha256
FROM    idps
WHERE   x509_new IS NOT NULL
  AND   x509_new != ''
);

CALL DropColumn('idps', 'x509');
CALL DropColumn('idps', 'x509_new');

-- IDP ENDPOINTS
CREATE TABLE IF NOT EXISTS `idps_endpoints`
(
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idp                 INT UNSIGNED NOT NULL,
    binding             TINYINT UNSIGNED NOT NULL,
    location            VARCHAR(255) NOT NULL,
    is_slo              TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),

    CONSTRAINT fk_idps_endpoints_idp_id
        FOREIGN KEY (idp)
        REFERENCES idps(id)
        ON DELETE CASCADE,

    UNIQUE KEY uniq_idp_bdg_loc (idp, binding, location)
);
-- SSO
INSERT IGNORE INTO idps_endpoints (idp, binding, location)
(
SELECT  id       AS idp,
        0        AS binding,
        sso_url  AS location
FROM    idps
WHERE sso_binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
);
INSERT IGNORE INTO idps_endpoints (idp, binding, location)
(
SELECT  id       AS idp,
        1        AS binding,
        sso_url  AS location
FROM    idps
WHERE sso_binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
);
-- SLO
INSERT IGNORE INTO idps_endpoints (idp, binding, is_slo, location)
(
SELECT  id       AS idp,
        0        AS binding,
        1        AS is_slo,
        slo_url  AS location
FROM    idps
WHERE slo_binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
);
INSERT IGNORE INTO idps_endpoints (idp, binding, is_slo, location)
(
SELECT  id       AS idp,
        1        AS binding,
        1        AS is_slo,
        slo_url  AS location
FROM    idps
WHERE slo_binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
);

CALL DropColumn('idps', 'sso_url');
CALL DropColumn('idps', 'sso_binding');
CALL DropColumn('idps', 'slo_url');
CALL DropColumn('idps', 'slo_binding');
DELETE FROM config WHERE conf_name = 'saml_acs_binding';
DELETE FROM config WHERE conf_name = 'saml_slo_binding';

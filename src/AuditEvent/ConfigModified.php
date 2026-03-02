<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;
use Override;

final class ConfigModified extends AbstractAuditEvent
{
    public function __construct(private string $name, private string $old, private string $new)
    {
        parent::__construct();
    }

    #[Override]
    public function getBody(): string
    {
        $this->filterSensitive();
        return sprintf('Instance configuration %s has been modified from %s to %s', $this->name, $this->old, $this->new);
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::ConfigModified;
    }

    private function filterSensitive(): void
    {
        $sensitiveColumns = array(
            'smtp_password',
            'ts_password',
            'saml_privatekey',
            'dspace_password',
            // not sensitive but we don't want to log it in full
            'saml_x509',
            'saml_x509_new',
            // not interesting
            'schema',
            'ldap_password',
            'remote_dir_config',
        );
        if (in_array($this->name, $sensitiveColumns, true)) {
            $this->old = 'something';
            $this->new = 'something else';
        }
    }
}

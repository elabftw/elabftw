<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;

class ConfigModified extends AbstractAuditEvent
{
    public function __construct(private string $name, private string $old, private string $new)
    {
        parent::__construct();
    }

    public function getBody(): string
    {
        $this->filterSensitive();
        return sprintf('Instance configuration %s has been modified from %s to %s', $this->name, $this->old, $this->new);
    }

    public function getCategory(): int
    {
        return AuditCategory::ConfigModified->value;
    }

    private function filterSensitive(): void
    {
        $sensitiveColumns = array(
            'smtp_password',
            'ts_password',
            'saml_privatekey',
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

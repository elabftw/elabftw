<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;
use Override;

use function array_merge;

final class SignatureKeysCreated extends AbstractAuditEvent
{
    public function __construct(private string $keyId, private int $requesterUserid = 0, private int $targetUserid = 0)
    {
        parent::__construct($this->requesterUserid, $this->targetUserid);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf('Cryptographic keypair with id %s was created and associated with the user account', $this->keyId);
    }

    #[Override]
    public function getJsonBody(): string
    {
        $info = array_merge($this->getBaseInfo(), array('key_id' => $this->keyId));
        return json_encode($info, JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::SignatureKeysCreated;
    }
}

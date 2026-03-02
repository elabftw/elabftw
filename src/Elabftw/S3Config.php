<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

final class S3Config
{
    public function __construct(
        public string $bucketName = '',
        public string $region = 'fr-par',
        public string $endpoint = '',
        public string $pathPrefix = '',
        public bool $usePathStyleEndpoint = false,
        public bool $verifyCert = true,
    ) {}
}

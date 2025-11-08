<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

use Override;

/**
 * S3 Exports have their own configurable path
 */
final class S3Exports extends S3
{
    #[Override]
    public function getPathPrefix(): string
    {
        return $this->config->configArr['s3_exports_path'];
    }
}

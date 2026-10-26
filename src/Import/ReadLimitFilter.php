<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Override;
use php_user_filter;
use Throwable;

use function stream_bucket_append;
use function stream_bucket_make_writeable;

use const PSFS_PASS_ON;
use const PSFS_ERR_FATAL;

/** @property LimitedReadStream $params */
final class ReadLimitFilter extends php_user_filter
{
    #[Override]
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            try {
                $this->params->consume($bucket->datalen);
            } catch (Throwable) {
                // The limiter retains the original exception for the caller.
                // Signal a failed read without passing any excess bytes on.
                while (stream_bucket_make_writeable($in)) {
                }
                return PSFS_ERR_FATAL;
            }
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}

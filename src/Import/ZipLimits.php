<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Elabftw\Env;
use InvalidArgumentException;

use function filter_var;
use function sprintf;

use const FILTER_VALIDATE_INT;

/**
 * Server-side limits, independent of the compressed HTTP upload limit.
 */
final readonly class ZipLimits
{
    public function __construct(
        public int $maxMembers = 10000,
        public int $maxMemberBytes = 1073741824,
        public int $maxTotalBytes = 1073741824,
        public int $maxCompressionRatio = 1000,
        public int $maxMetadataBytes = 33554432,
    ) {
        if ($maxMembers < 1 || $maxMemberBytes < 1 || $maxTotalBytes < 1 || $maxCompressionRatio < 1 || $maxMetadataBytes < 1) {
            throw new InvalidArgumentException('ZIP import limits must be positive integers.');
        }
    }

    public static function fromEnvironment(): self
    {
        $defaults = new self();
        return new self(
            self::readLimit('IMPORT_MAX_MEMBERS', $defaults->maxMembers),
            self::readLimit('IMPORT_MAX_MEMBER_BYTES', $defaults->maxMemberBytes),
            self::readLimit('IMPORT_MAX_TOTAL_BYTES', $defaults->maxTotalBytes),
            self::readLimit('IMPORT_MAX_COMPRESSION_RATIO', $defaults->maxCompressionRatio),
            self::readLimit('IMPORT_MAX_METADATA_BYTES', $defaults->maxMetadataBytes),
        );
    }

    private static function readLimit(string $name, int $default): int
    {
        $value = Env::asString($name);
        if ($value === '') {
            return $default;
        }
        $limit = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        if ($limit === false) {
            throw new InvalidArgumentException(sprintf('%s must be a positive integer.', $name));
        }
        return $limit;
    }
}

<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use InvalidArgumentException;
use PDO;

use function count;
use function implode;
use function sprintf;

/**
 * Compounds and reaction components store their Open Babel FP2 fingerprints in tables
 * with the same layout: a key column and the 32 words of the fingerprint in fp0 to fp31.
 * The tables are only reachable through the named constructors, so table and column
 * names never come from the outside.
 */
final class FingerprintTable
{
    public const int WORDS = 32;

    private Db $Db;

    private function __construct(private string $table, private string $keyColumn)
    {
        $this->Db = Db::getConnection();
    }

    public static function forCompounds(): self
    {
        return new self('compounds_fingerprints', 'id');
    }

    public static function forReactionComponents(): self
    {
        return new self('reaction_component_fingerprints', 'component_id');
    }

    /** Fails if this key already has a fingerprint */
    public function insert(int $key, array $fingerprint): void
    {
        $this->write('INSERT', $key, $fingerprint);
    }

    public function replace(int $key, array $fingerprint): void
    {
        $this->write('REPLACE', $key, $fingerprint);
    }

    /**
     * Conditions selecting the rows that have every bit of the fingerprint set: this screens for
     * structures containing the query, with possible false positives but no false negatives.
     */
    public static function containsSql(array $fingerprint, string $alias = ''): string
    {
        $sql = '';
        foreach ($fingerprint as $index => $value) {
            // an empty word constrains nothing
            if ((int) $value === 0) {
                continue;
            }
            $sql .= sprintf(' AND (%sfp%d & %d) = %d', self::prefix($alias), $index, $value, $value);
        }
        return $sql;
    }

    /**
     * Conditions selecting the rows with the same fingerprint.
     * With $ignoreEmptyWords, only the words with bits set in the query are compared:
     * this is how the compounds search has always defined an exact match.
     */
    public static function equalsSql(array $fingerprint, string $alias = '', bool $ignoreEmptyWords = false): string
    {
        $sql = '';
        foreach ($fingerprint as $index => $value) {
            if ($ignoreEmptyWords && (int) $value === 0) {
                continue;
            }
            $sql .= sprintf(' AND %sfp%d = %d', self::prefix($alias), $index, $value);
        }
        return $sql;
    }

    /** fp0 to fp31, for queries that copy fingerprints */
    public static function wordColumns(string $alias = ''): string
    {
        $columns = array();
        for ($i = 0; $i < self::WORDS; $i++) {
            $columns[] = sprintf('%sfp%d', self::prefix($alias), $i);
        }
        return implode(', ', $columns);
    }

    private static function prefix(string $alias): string
    {
        return $alias === '' ? '' : $alias . '.';
    }

    private function write(string $verb, int $key, array $fingerprint): void
    {
        if (count($fingerprint) !== self::WORDS) {
            throw new InvalidArgumentException('Fingerprint payload must contain 32 integers');
        }
        $placeholders = array(':key');
        for ($i = 0; $i < self::WORDS; $i++) {
            $placeholders[] = sprintf(':fp%d', $i);
        }
        $req = $this->Db->prepare(sprintf(
            '%s INTO %s (%s, %s) VALUES (%s)',
            $verb,
            $this->table,
            $this->keyColumn,
            self::wordColumns(),
            implode(', ', $placeholders),
        ));
        $req->bindValue(':key', $key, PDO::PARAM_INT);
        for ($i = 0; $i < self::WORDS; $i++) {
            $req->bindValue(sprintf(':fp%d', $i), (int) $fingerprint[$i], PDO::PARAM_INT);
        }
        $this->Db->execute($req);
    }
}

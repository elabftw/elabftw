<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Interfaces;

interface ContentParamsInterface
{
    public function getUnfilteredContent(): string;

    public function getContent(): string;

    public function getTarget(): string;

    public function getInt(): int;

    public function getColumn(): string;

    public function getBody(): string;

    public function getExtra(string $key): string;

    public function getUrl(): string;

    public function getPermissions(): string;
}

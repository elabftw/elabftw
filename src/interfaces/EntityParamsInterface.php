<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Interfaces;

interface EntityParamsInterface extends ContentParamsInterface
{
    public function getTitle(): string;

    public function getTags(): array;

    public function getDate(): string;

    public function getBody(): string;

    public function getExtraBody(): string;

    public function getRating(): int;

    public function getMetadata(): string;

    public function getField(): string;

    public function getUserId(): int;

    public function getState(): int;
}

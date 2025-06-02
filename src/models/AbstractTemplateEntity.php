<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Override;

use function is_array;
use function is_string;
use function json_encode;

/**
 * An entity like Templates or ItemsTypes. Template as opposed to Concrete: Experiments and Items
 */
abstract class AbstractTemplateEntity extends AbstractEntity
{
    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        // force tags to be an array
        $tags = $reqBody['tags'] ?? null;
        if (is_string($tags)) {
            $tags = array($tags);
        }
        // force metadata to be a string
        $metadata = $reqBody['metadata'] ?? null;
        if (is_array($metadata)) {
            $metadata = json_encode($metadata, JSON_THROW_ON_ERROR, 128);
        }
        return match ($action) {
            Action::Create => $this->create(
                title: $reqBody['title'] ?? null,
                template: $reqBody['template'] ?? -1,
                body: $reqBody['body'] ?? null,
                canread: $reqBody['canread'] ?? null,
                canreadIsImmutable: (bool) ($reqBody['canread_is_immutable'] ?? false),
                canwrite: $reqBody['canwrite'] ?? null,
                canwriteIsImmutable: (bool) ($reqBody['canwrite_is_immutable'] ?? false),
                tags: $tags ?? array(),
                category: $reqBody['category'] ?? null,
                status: $reqBody['status'] ?? null,
                metadata: $metadata,
                rating: $reqBody['rating'] ?? 0,
                contentType: $reqBody['content_type'] ?? null,
            ),
            Action::Duplicate => $this->duplicate((bool) ($reqBody['copyFiles'] ?? false), (bool) ($reqBody['linkToOriginal'] ?? false)),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
    }
}

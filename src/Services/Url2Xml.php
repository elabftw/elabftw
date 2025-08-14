<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use DOMDocument;
use Elabftw\Exceptions\ImproperActionException;

/**
 * Fetch XML data from an URL and convert to DOMDocument
 */
final class Url2Xml
{
    public function __construct(private HttpGetter $getter, private string $url, private DOMDocument $dom) {}

    public function getXmlDocument(): DOMDocument
    {
        $xml = $this->getter->get($this->url);
        if (empty($xml)) {
            throw new ImproperActionException('Could not get XML content!');
        }
        $res = $this->dom->loadXML($xml);
        if (!$res) {
            throw new ImproperActionException('Could not load XML content!');
        }
        return $this->dom;
    }
}

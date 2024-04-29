<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\MakeTrustedTimestampInterface;
use ZipArchive;

/**
 * Timestamp an experiment with RFC 3161 protocol: https://www.ietf.org/rfc/rfc3161.txt
 * Originally based on: https://d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161/
 */
abstract class AbstractMakeTrustedTimestamp extends AbstractMakeTimestamp implements MakeTrustedTimestampInterface
{
    /**
     * Create a zip archive with the timestamped data and the asn1 token
     */
    public function saveTimestamp(TimestampResponse $tsResponse, CreateUploadParamsInterface $create): int
    {
        // e.g. 20220210171842-timestamp.zip
        $zipName = $create->getFileName();
        // e.g. 20220210171842-timestamp.(json|pdf)
        $dataName = str_replace('zip', $this->dataFormat->value, $zipName);
        $tokenName = str_replace('zip', 'asn1', $zipName);

        // update timestamp on the experiment
        $this->updateTimestamp($this->formatResponseTime($tsResponse->getTimestampFromResponseFile()));

        $ZipArchive = new ZipArchive();
        $ZipArchive->open($create->getFilePath(), ZipArchive::CREATE);
        $ZipArchive->addFile($tsResponse->dataPath, $dataName);
        $ZipArchive->addFile($tsResponse->tokenPath, $tokenName);
        $ZipArchive->close();
        return $this->Entity->Uploads->create($create);
    }

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    abstract public function getTimestampParameters(): array;

    /**
     * Convert the time found in the response file to the correct format for sql insertion
     */
    protected function formatResponseTime(string $timestamp): string
    {
        $time = strtotime($timestamp);
        if ($time === false) {
            throw new ImproperActionException('Could not get response time!');
        }
        return date('Y-m-d H:i:s', $time);
    }
}

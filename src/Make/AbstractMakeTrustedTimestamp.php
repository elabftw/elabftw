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

use DateTimeImmutable;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\MakeTrustedTimestampInterface;
use ZipArchive;
use Override;

use function date;
use function is_array;
use function implode;
use function preg_last_error_msg;
use function preg_replace;
use function sprintf;
use function trim;

/**
 * Timestamp an experiment with RFC 3161 protocol: https://www.ietf.org/rfc/rfc3161.txt
 * Originally based on: https://d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161/
 */
abstract class AbstractMakeTrustedTimestamp extends AbstractMakeTimestamp implements MakeTrustedTimestampInterface
{
    /**
     * Create a zip archive with the timestamped data and the asn1 token
     */
    #[Override]
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
        return $this->entity->Uploads->create($create, isTimestamp: true);
    }

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    #[Override]
    abstract public function getTimestampParameters(): array;

    /**
     * Convert the time found in the response file to the correct format for sql insertion
     * PHP will take care of correct timezone conversions (if configured correctly)
     */
    protected function formatResponseTime(string $timestamp): string
    {
        // normalize whitespace to handle "Aug  3 ..." cases from OpenSSL
        $normalized = preg_replace('/\s+/', ' ', trim($timestamp));
        // Note: not sure how to test this code path...
        if ($normalized === null) {
            throw new ImproperActionException(sprintf('Error normalizing the timestamp: %s. %s', $timestamp, preg_last_error_msg()));
        }
        // first try with the microtime present
        $date = DateTimeImmutable::createFromFormat('M j H:i:s.u Y T', $normalized);
        if ($date instanceof DateTimeImmutable) {
            return date('Y-m-d H:i:s', $date->getTimestamp());
        }
        // try again but this time without microseconds as it might happen in some cases that it's not present
        $date = DateTimeImmutable::createFromFormat('M j H:i:s Y T', $normalized);
        // display a very descriptive error as to why it failed
        if (!$date instanceof DateTimeImmutable) {
            $errors = DateTimeImmutable::getLastErrors();
            $formattedErrors = '';
            if (is_array($errors)) {
                $formattedErrors = sprintf(
                    ' Found %d errors: %s',
                    $errors['error_count'],
                    implode(', ', $errors['errors']),
                );
            }
            throw new ImproperActionException(sprintf(
                'Could not format response time from timestamp: %s.%s',
                $timestamp,
                $formattedErrors,
            ));
        }
        return date('Y-m-d H:i:s', $date->getTimestamp());
    }
}

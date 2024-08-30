<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum ExportFormat: string
{
    case Binary = 'binary';
    case Csv = 'csv';
    case Eln = 'eln';
    case Json = 'json';
    case QrPdf = 'qrpdf';
    case QrPng = 'qrpng';
    case Pdf = 'pdf';
    case PdfA = 'pdfa';
    case SchedulerReport = 'schedulerReport';
    case SysadminReport = 'report';
    case TeamReport = 'teamReport';
    case Zip = 'zip';
    case ZipA = 'zipa';

    // create a comma separated list of values
    public static function toCsList(): string
    {
        $values = array_map(fn($case) => $case->value, self::cases());
        return implode(', ', $values);
    }
}

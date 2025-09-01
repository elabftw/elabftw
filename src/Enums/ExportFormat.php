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

use Elabftw\Traits\EnumsTrait;

enum ExportFormat: string
{
    use EnumsTrait;

    case Binary = 'binary';
    case Csv = 'csv';
    case Eln = 'eln';
    case ElnHtml = 'elnhtml';
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
}

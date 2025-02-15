<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

/**
 * Define extensions groups
 */
final class Extensions
{
    public const array ARCHIVE = array(
        'zip',
        'rar',
        'xz',
        'gz',
        'tgz',
        '7z',
        'bz2',
        'tar',
    );

    public const array AUDIO = array(
        'aac',
        'flac',
        'm4a',
        'mp3',
        'mp4',
        'ogg',
        'wav',
        'webm',
    );

    public const array CODE = array(
        'py',
        'jupyter',
        'js',
        'm',
        'r',
        'R',
    );

    public const array DOCUMENT = array(
        'doc',
        'docx',
        'odt',
    );

    public const array IMAGE = array(
        'gif',
        'heic',
        'jpeg',
        'jpg',
        'png',
        'tif',
        'tiff',
        'webp',
    );

    public const array HAS_EXIF = array(
        'jpeg',
        'jpg',
    );

    // list of extensions understood by 3Dmol.js see http://3dmol.csb.pitt.edu/doc/types.html
    public const array MOLECULE = array(
        'cdjson',
        'cif',
        'cube',
        'gro',
        'mcif',
        'mmtf',
        'mol2',
        'pdb',
        'pqr',
        'prmtop',
        'sdf',
        'vasp',
        'xyz',
    );

    public const array PRESENTATION = array(
        'ppt',
        'pptx',
        'pps',
        'ppsx',
        'odp',
    );

    public const array SPREADSHEET = array(
        'xls',
        'xlsx',
        'ods',
        'csv',
    );

    public const array VIDEO = array(
        'mov',
        'avi',
        'wmv',
        'mpeg',
        'flv',
    );

    public const array DNA = array(
        // DNA FASTA files
        // this is problematic because of protein fasta files and the multi-fasta format
        // only the first entry is shown for dna multi-fasta files
        'fasta',
        'fas',
        'fa',
        'fna',
        'ffn',
        // GENBANK files (.gb, .gbk)
        'gb',
        'gbk',
        // GENBANK protein files (.gp)
        'gp',
        // APE files (.ape), basically genbank files
        'ape',
        // SBOL files (.xml)
        // deactivated because xml is used for other data too
        //'xml',
        // SNAPGENE (.dna) files
        'dna',
        // GFF files
        'gff',
        'gff3',
    );

    /**
     * Get the correct class for icon from the extension
     *
     * @param string $ext Extension of the file
     * @return string Class of the fa icon
     * @psalm-suppress PossiblyUnusedMethod this method is used as a twig function ext2icon
     */
    public static function getIconFromExtension(string $ext): string
    {
        if (in_array($ext, self::ARCHIVE, true)) {
            return 'fa-file-archive';
        }
        if (in_array($ext, self::CODE, true)) {
            return 'fa-file-code';
        }
        if (in_array($ext, self::SPREADSHEET, true)) {
            return 'fa-file-excel';
        }
        if (in_array($ext, self::IMAGE, true)) {
            return 'fa-file-image';
        }
        if ($ext === 'pdf') {
            return 'fa-file-pdf';
        }
        if (in_array($ext, self::PRESENTATION, true)) {
            return 'fa-file-powerpoint';
        }
        if (in_array($ext, self::VIDEO, true)) {
            return 'fa-file-video';
        }
        if (in_array($ext, self::DOCUMENT, true)) {
            return 'fa-file-word';
        }

        return 'fa-file';
    }
}

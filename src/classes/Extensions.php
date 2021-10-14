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
    /** @var array ARCHIVE archive files */
    public const ARCHIVE = array(
        'zip',
        'rar',
        'xz',
        'gz',
        'tgz',
        '7z',
        'bz2',
        'tar',
    );

    /** @var array CODE source files */
    public const CODE = array(
        'py',
        'jupyter',
        'js',
        'm',
        'r',
        'R',
    );

    /** @var array DOCUMENT document files */
    public const DOCUMENT = array(
        'doc',
        'docx',
        'odt',
    );

    /** @var array IMAGE image files */
    public const IMAGE = array(
        'gif',
        'heic',
        'jpeg',
        'jpg',
        'png',
        'tif',
        'tiff',
        'webp',
    );

    /** @var array HAS_EXIF images with exif metadata */
    public const HAS_EXIF = array(
        'jpeg',
        'jpg',
    );

    /**
     * @var array 3DMOL list of extensions understood by 3Dmol.js see http://3dmol.csb.pitt.edu/doc/types.html
     * @norector \Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector
     */
    public const MOLECULE = array(
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

    /** @var array PRESENTATION presentation files */
    public const PRESENTATION = array(
        'ppt',
        'pptx',
        'pps',
        'ppsx',
        'odp',
    );

    /** @var array SPREADSHEET spreadsheet files */
    public const SPREADSHEET = array(
        'xls',
        'xlsx',
        'ods',
        'csv',
    );

    /** @var array VIDEO video files */
    public const VIDEO = array(
        'mov',
        'avi',
        'mp4',
        'wmv',
        'mpeg',
        'flv',
    );

    /** @var array DNA dna files */
    public const DNA = array(
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
}

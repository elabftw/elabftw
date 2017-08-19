<?php
/**
 * make.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Create a csv, zip or pdf file
 *
 */

require_once 'app/init.inc.php';
$pageTitle = _('Export');

try {
    if ($Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($Users);
    } else {
        $Entity = new Database($Users);
    }

    switch ($Request->query->get('what')) {
        case 'csv':
            $Make = new MakeCsv($Entity, $Request->query->get('id'));
            break;

        case 'zip':
            $Make = new MakeZip($Entity, $Request->query->get('id'));
            break;

        case 'pdf':
            $Entity->setId($Request->query->get('id'));
            $Make = new MakePdf($Entity);
            break;

        default:
            throw new Exception(Tools::error());
    }

    // the pdf is shown directly, but for csv or zip we want a download page
    if ($Request->query->get('what') === 'csv' || $Request->query->get('what') === 'zip') {

        $filesize = Tools::formatBytes(filesize($Make->filePath));
        require_once 'app/head.inc.php';

        echo $Twig->render('make.html', array(
            'what' => $Request->query->get('what'),
            'Make' => $Make,
            'filesize' => $filesize
        ));
        require_once 'app/footer.inc.php';
    }

} catch (Exception $e) {
    require_once 'app/head.inc.php';
    echo Tools::displayMessage($e->getMessage(), 'ko');
}

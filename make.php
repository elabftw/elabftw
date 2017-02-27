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
$selectedMenu = null;

try {
    $Users = new Users($_SESSION['userid']);

    if ($_GET['type'] === 'experiments') {
        $Entity = new Experiments($Users);
    } else {
        $Entity = new Database($Users);
    }

    switch ($_GET['what']) {
        case 'csv':
            $Make = new MakeCsv($Entity, $_GET['id']);
            break;

        case 'zip':
            $Make = new MakeZip($Entity, $_GET['id']);
            break;

        case 'pdf':
            $Entity->setId($_GET['id']);
            $Make = new MakePdf($Entity);
            break;

        default:
            throw new Exception(Tools::error());
    }

    // the pdf is shown directly, but for csv or zip we want a download page
    if ($_GET['what'] === 'csv' || $_GET['what'] === 'zip') {

        $filesize = Tools::formatBytes(filesize($Make->filePath));
        require_once 'app/head.inc.php';

        echo $twig->render('make.html', array(
            'what' => $_GET['what'],
            'Make' => $Make,
            'filesize' => $filesize
        ));
    }

} catch (Exception $e) {
    require_once 'app/head.inc.php';
    echo Tools::displayMessage($e->getMessage(), 'ko');

} finally {
    // this won't show up if it's a pdf
    require_once 'app/footer.inc.php';
}

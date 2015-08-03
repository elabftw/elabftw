<?php
/**
 * make.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Create a csv, zip or pdf file
 *
 */
use \Elabftw\Elabftw\Tools as Tools;

require_once 'inc/common.php';
$page_title = _('Export');
$selected_menu = null;

try {
    switch ($_GET['what']) {
        case 'csv':
            $make = new \Elabftw\Elabftw\MakeCsv($_GET['id'], $_GET['type']);
            break;

        case 'zip':
            $make = new \Elabftw\Elabftw\MakeZip($_GET['id'], $_GET['type']);
            break;

        case 'pdf':
            $make = new \Elabftw\Elabftw\MakePdf($_GET['id'], $_GET['type']);
            break;

        default:
            throw new Exception(_('Bad type!'));
    }
} catch (Exception $e) {
    require_once 'inc/head.php';
    display_message('error', $e->getMessage());
    require_once 'inc/footer.php';
    exit;
}

// the pdf is shown directly, but for csv or zip we want a download page
if ($_GET['what'] === 'csv' || $_GET['what'] === 'zip') {
    require_once 'inc/head.php';

    echo "<div class='well' style='margin-top:20px'>";
    echo "<p>" . _('Your file is ready:') . "<br>
            <a href='app/download.php?type=" . $_GET['what'] . "&f=" . $make->fileName . "&name=" . $make->getCleanName() . "' target='_blank'>
            <img src='img/download.png' alt='download' /> " . $make->getCleanName() . "</a>
            <span class='filesize'>(" . Tools::formatBytes(filesize($make->filePath)) . ")</span></p>";
    echo "</div>";

    require_once 'inc/footer.php';
}

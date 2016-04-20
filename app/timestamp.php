<?php
/**
 * timestamp.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Timestamp an experiment
 *
 */
try {
    require_once '../inc/common.php';

    $ts = new TrustedTimestamps($_GET['id']);
    $ts->timeStamp();

} catch (Exception $e) {
    $_SESSION['ko'][] = $e->getMessage();

} finally {
    header("Location: ../experiments.php?mode=view&id=" . $_GET['id']);
}

<?php
/**
 * app/controllers/MetaController.php
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
 * oO
 *
 */
require_once '../../inc/common.php';


try {
    // CREATE TAG
    if (isset($_POST['createTag'])) {
        if ($_POST['createTagType'] === 'experiments') {
            $entity = new Experiments($_SESSION['userid'], $_POST['createTagId']);
        } else {
            $entity = new Database($_SESSION['team_id']);
            $entity->setId($_POST['createTagId']);
        }
        $tags = new Tags($_POST['createTagType']);
        $tags->create($_POST['createTagTag'], $entity->id);
    }
} catch (Exception $e) {
    //      _                      _______                      _
    //   _dMMMb._              .adOOOOOOOOOba.              _,dMMMb_
    //  dP'  ~YMMb            dOOOOOOOOOOOOOOOb            aMMP~  `Yb
    //  V      ~"Mb          dOOOOOOOOOOOOOOOOOb          dM"~      V
    //           `Mb.       dOOOOOOOOOOOOOOOOOOOb       ,dM'
    //            `YMb._   |OOOOOOOOOOOOOOOOOOOOO|   _,dMP'
    //       __     `YMMM| OP'~"YOOOOOOOOOOOP"~`YO |MMMP'     __
    //     ,dMMMb.     ~~' OO     `YOOOOOP'     OO `~~     ,dMMMb.
    //  _,dP~  `YMba_      OOb      `OOO'      dOO      _aMMP'  ~Yb._
    // <MMP'     `~YMMa_   YOOo   @  OOO  @   oOOP   _adMP~'      `YMM>
    //              `YMMMM\`OOOo     OOO     oOOO'/MMMMP'
    //      ,aa.     `~YMMb `OOOb._,dOOOb._,dOOO'dMMP~'       ,aa.
    //    ,dMYYMba._         `OOOOOOOOOOOOOOOOO'          _,adMYYMb.
    //   ,MP'   `YMMba._      OOOOOOOOOOOOOOOOO       _,adMMP'   `YM.
    //   MP'        ~YMMMba._ YOOOOPVVVVVYOOOOP  _,adMMMMP~       `YM
    //   YMb           ~YMMMM\`OOOOI`````IOOOOO'/MMMMP~           dMP
    //    `Mb.           `YMMMb`OOOI,,,,,IOOOO'dMMMP'           ,dM'
    //      `'                  `OObNNNNNdOO'                   `'
    //                            `~OOOOO~'   TISSUE
}

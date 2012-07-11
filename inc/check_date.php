<?php
/******************************************************************************
*   Copyright 2012 Nicolas CARPi
*   This file is part of eLabFTW. 
*
*    eLabFTW is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    eLabFTW is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.
*
********************************************************************************/
require_once('inc/functions.php');
// Check DATE (is != null ? is 6 in length ? is int ? is valable ?)
if ((isset($_POST['date'])) 
    && (!empty($_POST['date'])) 
    && ((strlen($_POST['date']) == "6")) 
    && is_pos_int($_POST['date'])) {
    // Check if day/month are good
    $datemonth = substr($_POST['date'],2,2);
    $dateday = substr($_POST['date'],4,2);
    if(($datemonth <= "12") 
        && ($dateday <= "31") 
        && ($datemonth > "0") 
        && ($dateday > "0")){
            // SUCCESS on every test
    $date = $_POST['date'];
    } else {
    $msg_arr[] = 'You need to put a date in the correct format (YYMMDD) !';
    $date = kdate();
    $errflag = true;
    }
} else {
    $msg_arr[] = 'You need to put a date in the correct format (YYMMDD) !';
    $date = kdate();
    $errflag = true;
}
?>

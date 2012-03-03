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
// Check OUTCOME
if ((isset($_POST['outcome'])) 
    && (!empty($_POST['outcome']))){
    if (($_POST['outcome'] === 'running')
    || ($_POST['outcome'] === 'success')
    || ($_POST['outcome'] === 'fail')
    || ($_POST['outcome'] === 'redo')) {
    $outcome = $_POST['outcome'];
    }
} else {
    die("<p>What are you doing, Dave ?</p>");
}

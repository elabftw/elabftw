<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
namespace Elabftw\Elabftw;

use Exception;

try {
    require_once '../inc/common.php';

    // default location for redirect
    $location = '../login.php';

    $formKey = new FormKey();
    $Auth = new Auth();

    // Check the form_key
    if (!isset($_POST['formkey']) || !$formKey->validate()) {
        throw new Exception(_("Your session expired. Please retry."));
    }

    // Check email
    if ((!isset($_POST['email'])) || (empty($_POST['email']))) {
        throw new Exception(_('A mandatory field is missing!'));
    }

    // Check password is sent
    if ((!isset($_POST['password'])) || (empty($_POST['password']))) {
        throw new Exception(_('A mandatory field is missing!'));
    }

    // the actual login

    // this is here to avoid a notice Undefined index
    if (isset($_POST['rememberme'])) {
        $rememberme = $_POST['rememberme'];
    } else {
        $rememberme = 'off';
    }

    if ($Auth->login($_POST['email'], $_POST['password'], $rememberme)) {
        if (isset($_COOKIE['redirect'])) {
            $location = $_COOKIE['redirect'];
        } else {
            $location = '../experiments.php';
        }
    } else {
        // log the attempt if the login failed
        $Logs = new Logs();
        $Logs->create('Warning', $_SERVER['REMOTE_ADDR'], 'Failed login attempt');
        // inform the user
        $_SESSION['ko'][] = _("Login failed. Either you mistyped your password or your account isn't activated yet.");
        if (!isset($_SESSION['failed_attempt'])) {
            $_SESSION['failed_attempt'] = 1;
        } else {
            $_SESSION['failed_attempt'] += 1;
        }
    }
} catch (Exception $e) {
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    header("location: $location");
}

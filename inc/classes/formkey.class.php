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
// Note : for a page with several <form>, this will work only for 1 <form> !
namespace elabftw\elabftw;

class FormKey {
    // here we store the generated form key
    private $formkey;

    // here we store the old form key
    private $oldFormKey;

    // function to generate the form key
    private function generate_formkey() {
        // get ip of user
        $ip = $_SERVER['REMOTE_ADDR'];

        // add randomness (mt_rand() is better than rand())
        $uniqid = uniqid(mt_rand(), true);

        // return a md5 hash of all that
        return md5($ip . $uniqid);

    }

    public function output_formkey() {
        // generate the key and store it inside the class
        $this->formkey = $this->generate_formkey();
        // store the form key in the session
        $_SESSION['form_key'] = $this->formkey;
        // output the form key
        echo "<input type='hidden' name='form_key' id='form_key' value='" . $this->formkey . "' />";
    }

    //The constructor stores the form key (if one exists) in our class variable.
    function __construct() {
    //We need the previous key so we store it
    if (isset($_SESSION['form_key'])) {
        $this->oldFormKey = $_SESSION['form_key'];
    }
    }

    public function validate() {
        // we use the old formKey and not the new generated one
        return $_POST['form_key'] == $this->oldFormKey;
    }
}

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
/* install/install.php to get an installation up and running */
require_once('../inc/head.php');
// TODO check that it's not already installed (check for .ini file)
?>
<h2>Install eLabFTW</h2>
<form action='install/install-exec.php' method='post'>
Database location <input value='localhost' type='text' name='db_host' /><br />
Database name <input value='elabftw' type='text' name='db_name' /><br />
Database user <input placeholder='elabftw' type='text' name='db_user' /><br />
Database password <input placeholder='secr3t' type='password' name='db_password' /><br />

<hr>
Admin account <input type='text' name='username' /><br />
Admin password <input type='password' name='password' /><br />
Confirm password <input type='password' name='cpassword' /><br />
<br />
<br />
<input type='submit' value='INSTALL' />
</form>

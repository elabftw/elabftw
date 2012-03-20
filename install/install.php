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
?>
<h2>Install eLabFTW</h2>
<form action='install/install-exec.php' method='post'>
<h4>Database location</h4><input value='localhost' type='text' name='db_host' />
<h4>Database name</h4><input value='elabftw' type='text' name='db_name' />
<h4>Database user</h4><input placeholder='elabftw' type='text' name='db_user' />
<h4>Database password</h4><input placeholder='secr3t' type='password' name='db_password' />

<h4>Admin account</h4><input type='text' name='username' />
<h4>Admin password</h4><input type='text' name='password' />
<h4>Confirm password</h4><input type='text' name='cpassword' />
<br />
<br />
<input type='submit' value='INSTALL' />
</form>

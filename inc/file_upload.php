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
?>
<!-- FILE UPLOAD -->
<script>
$(document).ready(function(){
	$(".toggle_container").hide();
	$("h4.trigger").click(function(){
		$(this).toggleClass("active").next().slideToggle("slow");
	});
});
// javascript to add an upload field
function add_file_field(){
var container=document.getElementById('file_container');
var com_container=document.getElementById('filecomment_container');
var file_field=document.createElement('input');
var com_file_field=document.createElement('input');
file_field.name='files[]';
com_file_field.name='filescom[]';
com_file_field.setAttribute('size', '35');
com_file_field.setAttribute('placeholder', 'Enter a comment for the file');
com_file_field.setAttribute('class', 'com_file_field');
file_field.type='file';
container.appendChild(file_field);
container.appendChild(com_file_field);
var br_field=document.createElement('br');
container.appendChild(br_field);
}
</script>

<hr class='flourishes'>
<br />
<div class='attachFileDiv'>
<h4 class='trigger'>Click to add a file</h4>
<div class='toggle_container'>
<div class='addFileDiv'>
<div id='file_container'>
    <input name="files[]" type="file"  />
    <input size='35' placeholder='Enter a comment for the file' name='filescom[]' />
  </div>
<?php 
if (basename($_SERVER['REQUEST_URI']) != 'team.php') { ?>
<br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/attach_add.png' alt='' /> <a href="javascript:void(0);" onClick="add_file_field();">Add another file</a><br />
<?php } ?>
</div>

<?php
// Show only a submit button on team page (for upload of labmeetings/journal clubs
if (basename($_SERVER['REQUEST_URI']) == 'team.php') {
?>
<!-- SUBMIT BUTTON -->
<div class='center' id='submitdiv'>
<input type='submit' name='Submit' value='Submit' />
</div>
<?php
} ?>
</div><!-- end toggle container -->
</div>
<hr class='flourishes'><!-- END FILE UPLOAD -->

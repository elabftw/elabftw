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
require_once('inc/common.php');
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
// Check id is valid and assign it to $id
if(isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}
// Switch exp/prot
if ($_GET['type'] === 'exp'){
    $table = 'experiments';
}elseif ($_GET['type'] === 'prot'){
    $table = 'protocols';
}else{
    die('bad type');
}

// SQL to get title, body and date
$sql = "SELECT title, body, date, userid FROM ".$table." WHERE id = $id";
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
// problem : fpdf is not utf-8 aware...
    $title = stripslashes(str_replace("&#39;", "'", utf8_decode($data['title'])));
    $date = $data['date'];
    $body = stripslashes(str_replace("&#39;", "'", utf8_decode($data['body'])));
$req->closeCursor();

// SQL to get firstname + lastname
$sql = "SELECT firstname,lastname FROM users WHERE userid = ".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
$firstname = $data['firstname'];
$lastname = $data['lastname'];
// SQL to get tags
$sql = "SELECT tag FROM ".$table."_tags WHERE item_id = $id";
$req = $bdd->prepare($sql);
$req->execute();
$tags = NULL;
while($data = $req->fetch()){
    $tags .= stripslashes(str_replace("&#39;", "'", utf8_decode($data['tag']))).' ';
}


// SQL to get filesattached
$sql = "SELECT real_name, long_name, comment FROM uploads WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$real_name = array();
$long_name = array();
$comment = array();
while ($data = $req->fetch()) {
    $real_name[] = $data['real_name'];
    $long_name[] = $data['long_name'];
    $comment[] = $data['comment'];
}


// BEGIN ZIP
$zipname = $date.'-'.preg_replace('/[^A-Za-z0-9]/', '_', $title);
$zip = new ZipArchive;
$res = $zip->open('uploads/'.$zipname.'.zip' , ZipArchive::CREATE);
if ($res === TRUE) {
    $experiment ='Date : '.$date.'
By : '.$firstname.' '.$lastname.'
Title : '.$title.'
Text : 
'.$body.'
        
';
    // files attached ?
    $filenb = count($real_name);
    if ($filenb > 0){
        if ($filenb == 1){
            $files = '~~~~
Attached file :
';
        } else {
            $files = '~~~~
Attached files :
';
        }
        for ($i=0;$i<$filenb;$i++){
            $files .= $real_name[$i].' ('.stripslashes(str_replace("&#39;", "'", utf8_decode($comment[$i]))).')
';
            // add files to archive
            $zip->addFile('uploads/'.$long_name[$i], $real_name[$i]);
        }
    } else { // if no files attached
        $files = '';
    }

    // FOOTER
    $footer = '
~~~~
    File created with elabFTW -- Free open source lab manager
    http://www.elabftw.net';
    // CREATE TXT FILE
    // fix bad encoding
    $files = utf8_encode($files);
    $experiment = utf8_encode($experiment);
    // add header for utf-8
    $content = "\xEF\xBB\xBF".$experiment.$files.$footer;
    $txtfile = $zipname.'.txt';
    $tf = fopen($txtfile, 'w');
    fwrite($tf, $content);
    fclose($tf);
    $zip->addFile($txtfile);
    $zip->close();
    // delete txt file
    unlink($txtfile);

    // PAGE BEGIN
    echo "<h3>CREATE ZIP ARCHIVE</h3>";
    echo "<div class='item'>";
    echo "<p>Adding text file :<br />".$zipname.".txt</p>";
    if ($filenb > 0){
        if ($filenb == 1){
            echo "Adding file :<br /><ol>";
        } else {
            echo "Adding files :<br /><ol>";
        }
        for ($i=0;$i<$filenb;$i++){
            echo "<li>".$real_name[$i]." (".stripslashes($comment[$i]).")</li>";
        }
        echo "</ol>";
    }
    echo "<hr>";
    echo "<p>Download archive :<br /><img src='themes/".$_SESSION['prefs']['theme']."/img/download.png' alt='' /> <a href='uploads/".$zipname.".zip'>".$zipname.".zip</a></p>";
    // SQL to get all users and emails
    $sql = "SELECT firstname, lastname, email, userid FROM users";
    $req = $bdd->prepare($sql);
    $req->execute();
    echo "<p>Send zip archive to :
        <form style='margin-top:-15px' method='post' action='send_zip.php'><img src='themes/".$_SESSION['prefs']['theme']."/img/mail.gif' alt='mail' /> <select name='userid'>";
    while($data = $req->fetch()){
         echo "<option value='".$data['userid']."'>".$data['firstname']." ".$data['lastname']."</option>";
    }
    echo "</select> <input type=submit value='send' />
        <input type='hidden' name='zipname' value='".$zipname."'>
        </form></p></div>";
} else {
        echo 'Archive creation failed :(';
}

require_once('inc/footer.php');

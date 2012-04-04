<?php
function kdate(){
    // returns today's date as YYMMDD format
    $today = getdate();
    $year = substr($today['year'], -2);
    $month = $today['mon'];
    if (strlen($month) === 1){
        $month = "0".$month;
    }
    $day = $today['mday'];
    if (strlen($day) === 1){
        $day = "0".$day;
    }
    return $year.$month.$day;
}

//function daydiff($date){
//// returns the number of days between now and a $date in epoch format
//$currtime =  time();
//$timediff = $currtime - $date;
//$daydiff = floor((($timediff / 60) /60) / 24);
//return $daydiff;
//}

function format_bytes($a_bytes){
    // nice display of filesize
if ($a_bytes < 1024) {
return $a_bytes .' B';
} elseif ($a_bytes < 1048576) {
return round($a_bytes / 1024, 2) .' KiB';
} elseif ($a_bytes < 1073741824) {
return round($a_bytes / 1048576, 2) . ' MiB';
} elseif ($a_bytes < 1099511627776) {
return round($a_bytes / 1073741824, 2) . ' GiB';
} elseif ($a_bytes < 1125899906842624) {
return round($a_bytes / 1099511627776, 2) .' TiB';
} elseif ($a_bytes < 1152921504606846976) {
return round($a_bytes / 1125899906842624, 2) .' PiB';
} elseif ($a_bytes < 1180591620717411303424) {
return round($a_bytes / 1152921504606846976, 2) .' EiB';
} elseif ($a_bytes < 1208925819614629174706176) {
return round($a_bytes / 1180591620717411303424, 2) .' ZiB';
} else {
return round($a_bytes / 1208925819614629174706176, 2) .' YiB';
}
}

function createPassword($length) {
    $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $i = 0;
    $password = "k";
    while ($i < $length) {
    $password .= $chars{mt_rand(0,strlen($chars))};
    $i++;
    }
    return $password;
}

function get_ext($filename){
    // Get file extension
    $path_info = pathinfo($filename);
    return $path_info['extension'];
}


function make_thumb($src,$ext,$dest,$desired_width){
    // Create thumbnail from jpg, png or gif
    if($ext === 'jpg' || $ext === 'jpeg'){
        $source_image = imagecreatefromjpeg($src);
    }
    elseif($ext === 'png'){
        $source_image = imagecreatefrompng($src);
    }
    elseif($ext === 'gif'){
        $source_image = imagecreatefromgif($src);
    }
    $width = imagesx($source_image);
    $height = imagesy($source_image);

    // find the "desired height" of this thumbnail, relative to the desired width
    $desired_height = floor($height*($desired_width/$width));

    // create a new, "virtual" image
    $virtual_image = imagecreatetruecolor($desired_width,$desired_height);

    // copy source image at a resized size
    imagecopyresized($virtual_image,$source_image,0,0,0,0,$desired_width,$desired_height,$width,$height);

    // create the physical thumbnail image to its destination (85% quality)
    imagejpeg($virtual_image,$dest, 85);
}

function loadClass($class) {
    require_once('lib/classes/'.$class.'.class.php');
}

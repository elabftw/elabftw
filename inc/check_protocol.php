<?php
if (isset($_POST['protocol']) && $_POST['protocol'] != 'None' && filter_var($_POST['protocol'], FILTER_VALIDATE_INT)) {
    $prot_id = $_POST['protocol'];
} else {
    $prot_id = NULL;
}

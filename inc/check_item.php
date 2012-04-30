<?php
if (isset($_POST['item']) && $_POST['item'] != 'None' && filter_var($_POST['item'], FILTER_VALIDATE_INT)) {
    $linked_item_id = $_POST['item'];
} else {
    $linked_item_id = NULL;
}

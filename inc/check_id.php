<?php
if (isset($_POST['item_id']) && !empty($_POST['item_id'])) {
    $filter_options = array(
        'options' => array(
            'min_range' => 1
        ));
    $id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT, $filter_options);
}
?>

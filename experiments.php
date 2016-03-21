<?php
/**
 * experiments.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Entry point for all experiment stuff
 *
 */
require_once 'inc/common.php';
$page_title = ngettext('Experiment', 'Experiments', 2);
$selected_menu = 'Experiments';
require_once 'inc/head.php';

// add the chemdoodle stuff if we want it
echo addChemdoodle();

if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
    require_once 'inc/showXP.php';

// VIEW
} elseif ($_GET['mode'] === 'view') {

    try {
        $experimentsView = new \Elabftw\Elabftw\ExperimentsView($_GET['id']);
        echo $experimentsView->view();
    } catch (Exception $e) {
        display_message('ko', $e->getMessage());
        require_once 'inc/footer.php';
        exit;
    }

    // TODO
    $id = $_GET['id'];
    // DISPLAY FILES
    require_once 'inc/display_file.php';

    echo $experimentsView->showComments($id);
    echo $experimentsView->showCommentsCreate($id);
    ?>
    <script>
    function commentsUpdate() {
        // Experiment comment is editable
        $('div#expcomment').on("mouseover", ".editable", function(){
            $('div#expcomment p.editable').editable('app/controllers/CommentsController.php', {
                name: 'commentsUpdateComment',
                tooltip : 'Click to edit',
                indicator : '<?php echo _('Saving'); ?>',
                commentsUpdate: true,
                submit : '<?php echo _('Save'); ?>',
                cancel : '<?php echo _('Cancel'); ?>',
                style : 'display:inline',
                callback : function() {
                    // now we reload the comments part to show the comment we just submitted
                    $('#expcomment_container').load("experiments.php?mode=view&id=<?php echo $id; ?> #expcomment");
                    // we reload the function so editable zones are editable again
                    commentsUpdate();
                }
            })
        });
    }

    // READY ? GO !!
    $(document).ready(function() {
        $('#commentsCreateButtonDiv').hide();

        // change title
        // fix for the ' and "
        //$('.title_view').replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
        // Keyboard shortcuts
        key('<?php echo $_SESSION['prefs']['shortcuts']['create']; ?>', function(){location.href = 'app/create_item.php?type=exp'});
        key('<?php echo $_SESSION['prefs']['shortcuts']['edit']; ?>', function(){location.href = 'experiments.php?mode=edit&id=<?php echo $id; ?>'});
        // make editable
        setInterval(commentsUpdate, 50);
    });
    </script>
    <?php
} elseif ($_GET['mode'] === 'edit') {
    try {
        $experimentsView = new \Elabftw\Elabftw\ExperimentsView($_GET['id']);
        echo $experimentsView->edit();
        require_once 'inc/editXP.php';
    } catch (Exception $e) {
        display_message('ko', $e->getMessage());
        require_once 'inc/footer.php';
        exit;
    }
} else {
    require_once 'inc/showXP.php';
}

require_once 'inc/footer.php';

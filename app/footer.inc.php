<?php
/**
 * app/footer.inc.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */
?>
<!-- TODOLIST -->
<div id='todoList'>
    <form id="todo-form">
        <input id="todo" type="text" />
        <input id="submit" type="submit" class='button' value="TODOfy">
    </form>
   <ul id="show-items"></ul>
    <br><br>
    <a class='button' href="#" onClick='toggleTodoList()'>Close</a>
    <br><br>
    <a href="#" style='float:left' id="clear-all">Clear All</a>
</div>
<!-- END TODOLIST -->

<footer>

    <p class='footer_left'>
    <a class='elab-tooltip-top' href='https://twitter.com/elabftw'>
        <span>Follow eLabFTW on Twitter!</span>
    <img src='img/twitter.png' alt='twitter' />
    </a>
     <a class='elab-tooltip-top' href='https://github.com/elabftw/elabftw'>
        <span>eLabFTW on GitHub</span>
    <img src='img/github.png' alt='github' />
    </a>
    <span>
        <span>
<?php
if (isset($_SESSION['auth']) && $_SESSION['is_sysadmin']) {
    ?>
        <!-- SYSADMIN MENU -->
        <a href='sysconfig.php'><?= _('Sysadmin panel') ?></a>
    <?php
}
if (isset($_SESSION['auth']) && $_SESSION['is_admin']) {
    echo "<a href='admin.php'>" . _('Admin panel');
    $Users = new \Elabftw\Elabftw\Users();
    $unvalidated = count($Users->readAllFromTeam($_SESSION['team_id'], 0));
    if ($unvalidated > 0) {
        echo " <span class='badge'>" . $unvalidated . "</span>";
    }
    echo "</a>";
}
echo "</span></p><div class='footer_right'>";
echo _('Powered by') . " <a href='http://www.elabftw.net'>eLabFTW</a><br>";
echo _('Page generated in') . ' '; ?>
<span class='strong'><?= round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 5) ?> seconds</span>
(<?= $pdo->getNumberOfQueries() ?> SQL)
</div>
</footer>

<!-- advanced search div -->
<script>
$('#adv_search').hide();
$('#big_search_input').click(function() {
    $('#adv_search').show();
});
$('#help_container').hide();
$('#help').click(function() {
    $('#help_container').toggle();
});
</script>
<?php
if (isset($_SESSION['auth'])) {
    // show TODOlist
    echo "<script>
    key('" . $_SESSION['prefs']['shortcuts']['todo'] . "', function(){
        toggleTodoList();
    });
    </script>";
}
?>
</body>
</html>

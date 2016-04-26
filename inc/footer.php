<?php
/**
 * inc/footer.php
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
    <a href='https://twitter.com/elabftw'>
    <img src='img/twitter.png' alt='twitter' title='Follow eLabFTW on Twitter !'>
    </a>
     <a href='https://github.com/elabftw/elabftw'>
    <img src='img/github.png' alt='github' title='eLabFTW on GitHub'>
    </a>
    <span>
        <span class='strong'>
<?php
if (isset($_SESSION['auth']) && $_SESSION['is_sysadmin']) {
    ?>
        <!-- SYSADMIN MENU -->
        <a href='sysconfig.php'><?php echo _('Sysadmin panel'); ?></a>
    <?php
}
if (isset($_SESSION['auth']) && $_SESSION['is_admin']) {
    echo "<a href='admin.php'>" . _('Admin panel');
    // show counter of unvalidated users
    $sql = "SELECT count(validated) FROM users WHERE validated = 0 AND team = :team";
    $req = $pdo->prepare($sql);
    $req->bindValue(':team', $_SESSION['team_id']);
    $req->execute();
    $unvalidated = $req->fetchColumn();
    if ($unvalidated > 0) {
        echo " <span class='badge'>" . $unvalidated . "</span>";
    }
    echo "</a>";
}
echo "</span></p><div class='footer_right'>";
echo _('Powered by') . " <a href='http://www.elabftw.net'>eLabFTW</a><br>";
echo _('Page generated in') . ' '; ?>
<span class='strong'><?php echo round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 5); ?> seconds</span></div>
</footer>

<!-- advanced search div -->
<script>
$('#adv_search').hide();
$('#big_search_input').click(function() {
    $('#adv_search').show();
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

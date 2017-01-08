<?php
/**
 * app/footer.inc.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */
// TODOLIST
if (isset($_SESSION['auth'])) {
    $Todolist = new \Elabftw\Elabftw\Todolist($_SESSION['userid']);
    $todoItems = $Todolist->readAll();
    ?>
    <div id='todoList'>
    <script>
    // check for old style items and add them to SQL
    var orderList = localStorage.getItem('todo-orders');
    orderList = orderList ? orderList.split(',') : [];
    for( j = 0, k = orderList.length; j < k; j++) {
        $.post("app/controllers/TodolistController.php", {
            create: true,
            body: localStorage.getItem(orderList[j])
        });
    }
    // delete old style storage
    localStorage.clear();
    </script>
        <form id="todo-form">
            <input id="todo" type="text" />
            <input id="submit" type="submit" class='button' value="TODOfy">
        </form>
    <p>
       <ul id='todoItems-list'>
    <?php
    foreach ($todoItems as $todoItem) {
        echo "<li id='todoItem_" . $todoItem['id'] . "'><a href='#' onClick='destroyTodolist(" . $todoItem['id'] .
            ")'>X</a><span style='font-size:60%;display:block;'>" . $todoItem['creation_time'] .
            "</span><span id='todoItem_" . $todoItem['id'] . "' class='editable'>" . $todoItem['body'] . "</span></li>";
    }
    ?>
        </ul>
        <br><br>
        <a class='button' href="#" onClick='toggleTodoList()'>Close</a>
    </p>
        <a href="#" style='float:left' onClick='destroyAllTodolist()'>Clear All</a>
    </div>
    <?php
}
?>
<!-- END TODOLIST -->

<footer>

    <p class='footer_left'>
    <a class='elab-tooltip-top' href='https://twitter.com/elabftw'>
        <span>Follow eLabFTW on Twitter!</span>
    <img src='app/img/twitter.png' alt='twitter' />
    </a>
     <a class='elab-tooltip-top' href='https://github.com/elabftw/elabftw'>
        <span>eLabFTW on GitHub</span>
    <img src='app/img/github.png' alt='github' />
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
echo _('Powered by') . " <a href='https://www.elabftw.net'>eLabFTW</a><br>";
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
// SORTABLE for TODOLIST items
$('#todoItems-list').sortable({
    // limit to vertical dragging
    axis : 'y',
    helper : 'clone',
    // do ajax request to update db with new order
    update: function(event, ui) {
        // send the orders as an array
        var ordering = $("#todoItems-list").sortable("toArray");

        $.post("app/controllers/TodolistController.php", {
            'updateOrdering': true,
            'ordering': ordering
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
            } else {
                notif(json.msg, 'ko');
            }
        });
    }
});
$('#todoItems-list').on('mouseover', '.editable', function(){
    makeEditableTodoitem();
});

</script>
<?php
if (isset($_SESSION['auth'])) {
?>
<script>
    // Create
    $('#todo-form').submit(function(e) {
        e.preventDefault();
        var body = $('#todo').val();
        var currentdate = new Date();
        var datetime = currentdate.getFullYear() + "-" +
            (currentdate.getMonth()+1)  + "-" +
            currentdate.getDate() + " " +
            currentdate.getHours() + ":" +
            currentdate.getMinutes() + ":" +
            currentdate.getSeconds();
        if (body !== "") {
            $.post("app/controllers/TodolistController.php", {
                create: true,
                body: body
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    // add the todoitem
                    $('#todoItems-list').prepend("<li class='todoItem' id='todoItem_" +
                            json.id +
                            "'><a href='#' onClick='destroyTodolist(" +
                            json.id +
                            ")'>X</a><span style='font-size:60%;display:block;'>" +
                            datetime + "</span><span id='todoItem_" + json.id + "' class='editable'>" + body +
                            '</li>');
                    
                    // make it editable right away
                    makeEditableTodoitem();
                    // and clear the input
                    $('#todo').val("");
                } else {
                    notif(json.msg, 'ko');
                }
            });
        }
    });
    // show TODOlist
    key('<?= $_SESSION['prefs']['shortcuts']['todo'] ?>', function(){
        toggleTodoList();
    });
    </script>
<?php
}
?>
</body>
</html>

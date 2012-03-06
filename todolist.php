<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="css/todolist.css" type="text/css" />
    </head>
    <body>
        <div id="container">
            <form id="todo-form">
                <input id="todo" type="text" />
                <input id="submit" type="submit" value="TODOfy">
            </form>
            <ul id="show-items"></ul>
            <a href="#" id="clear-all">Clear All</a>
        </div>
        <script src="js/jquery-1.7.1.min.js"></script>
        <script src="js/jquery-ui-1.8.18.custom.min.js"></script>
        <script src="js/jquery.inlineedit.js"></script>
        <script src="js/pubsub.js"></script>
        <script src="js/todolist.js"></script>
    </body>
</html>

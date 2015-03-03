/*
 * todolist.js
 * this file is minified in todolist.min.js, and this is what is used in the code
 * so if you change stuff here, don't forget to minify ;)
 * part of elabftw.net project
 * adapted from http://web.koesbong.com/2011/01/24/sortable-and-editable-to-do-list-using-html5s-localstorage/
 */

$(function() {

    /*
     * FUNCTIONS
     */

    function remove($this) {
        var parentId = $this.parent().attr('id');
        // Remove todo list from localStorage based on the id of the clicked parent element
        localStorage.removeItem(
            "'" + parentId + "'"
        );
        // Fade out the list item then remove from DOM
        $this.parent().fadeOut(function() {
            $this.parent().remove();
            regenerate();
        });
    }

    function regenerate() {
        var $todoItemLi = $('#show-items li');
        // Empty the order array
        order.length = 0;

        // Go through the list item, grab the ID then push into the array
        $todoItemLi.each(function() {
            var id = $(this).attr('id');
            order.push(id);
        });

        // Convert the array into string and save to localStorage
        localStorage.setItem(
            'todo-orders', order.join(',')
        );
    }
    function clearAll() {
        var $todoListLi = $('#show-items li');

        order.length = 0;
        localStorage.clear();
        $todoListLi.remove();
    }

    /*
     * VARS
     */

    var i = Number(localStorage.getItem('todo-counter')) + 1,
        j = 0,
        k,
        $form = $('#todo-form'),
        $removeLink = $('#show-items li a'),
        $itemList = $('#show-items'),
        $clearAll = $('#clear-all'),
        $newTodo = $('#todo'),
        order = [],
        orderList;

    /*
     * START
     */

    // Initial loading of todo items
    orderList = localStorage.getItem('todo-orders');

    orderList = orderList ? orderList.split(',') : [];

    // display items
    for( j = 0, k = orderList.length; j < k; j++) {
        $itemList.append(
            "<li id='" + orderList[j] + "'>"
            + localStorage.getItem(orderList[j])
            + " <a href='#'>X</a></li>"
        );
    }


    // Add a todo item
    $form.submit(function(e) {
        e.preventDefault();
        if ($newTodo.val() !== "") {
            // Take the value of the input field and save it to localStorage
            localStorage.setItem(
                "todo-" + i, $newTodo.val()
            );

            // Set the todo max counter so on page refresh it keeps going up instead of reset
            localStorage.setItem('todo-counter', i);

            // Append a new list item with the value of the new todo list
            $itemList.append(
                "<li id='todo-" + i + "'>"
                + "<span class='editable'>"
                + localStorage.getItem("todo-" + i)
                + " </span><a href='#'>x</a></li>"
            );

            regenerate();

            // Hide the new list, then fade it in for effects
            $("#todo-" + i)
                .css('display', 'none')
                .fadeIn();

            // Empty the input field
            $newTodo.val("");

            i++;
        }
    });

    // Remove todo
    $itemList.delegate('a', 'click', function(e) {
        var $this = $(this);

        e.preventDefault();
        remove($this);
    });

    // Clear all
    $clearAll.click(function(e) {
        e.preventDefault();
        clearAll();
    });

    // Fade In and Fade Out the Remove link on hover
    $itemList.delegate('li', 'mouseover mouseout', function(event) {
        var $this = $(this).find('a');

        if (event.type === 'mouseover') {
            $this.stop(true, true).fadeIn();
        } else {
            $this.stop(true, true).fadeOut();
        }
    });
});

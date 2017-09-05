$(document).ready(function(){

    // validate the form upon change. fix #451
    $('.form-control').on('change', function() {
        $('#filter-order-sort').submit();
    });

    // bodyToggleImg is the little +/- image
    $('.bodyToggleImg').click(function() {
        // transform the + in - and vice versa
        if ($(this).attr('src') == 'app/img/show-more.png') {
            $(this).attr('src', 'app/img/show-less.png');
        } else {
            $(this).attr('src', 'app/img/show-more.png');
        }
        // get the id to show the toggleBody
        id = $(this).attr('id');
        idArr = id.split("_");
        id = idArr[1];
        // get html of body
        $.post('app/controllers/EntityController.php', {
            'getBody' : true,
            'id' : id,
            'type' : $(this).data('type')
        // and put it in the div and show the div
        }).done(function(data) {
            $('#bodyToggle_' + id).html(data.msg);
            // get the width of the parent. The -30 is to make it smaller than parent even with the margins
            var width = $('#parent_' + id).width() - 30;
            // adjust the width of the children
            $('#bodyToggle_' + id).css('width', width);
            // display div
            $('#bodyToggle_' + id).toggle();
        });
    });

    // there is a create shortcut only for experiments
    var page = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
    var controller = 'app/controllers/DatabaseController.php';
    if (page === 'experiments.php') {
        controller = 'app/controllers/ExperimentsController.php';

        // KEYBOARD SHORTCUT
        key($('#shortcuts').data('create'), function(){
            window.location.href = controller + '?create=true';
        });
    }

    // SHOW MORE BUTTON
    $('section.item').hide(); // hide everyone
    $('section.item').slice(0, $('#limit').data('limit')).show(); // show only the default at the beginning
    $('#loadButton').click(function(e){ // click to load more
        e.preventDefault();
        $('section.item:hidden').slice(0, $('#limit').data('limit')).show();
        if ($('section.item:hidden').length === 0) { // check if there are more exp to show
            $('#loadButton').hide(); // hide load button when there is nothing more to show
            $('#loadAllButton').hide(); // hide load button when there is nothing more to show
        }
    });
    $('#loadAllButton').click(function(e){ // click to load more
        e.preventDefault();
        $('section.item:hidden').show();
        $('#loadAllButton').hide(); // hide load button when there is nothing more to show
        $('#loadButton').hide(); // hide load button when there is nothing more to show
    });

    // TITLE CLICK
    /*
    $('.title').on('click', function() {
        window.location.href = '?mode=view&id=' + $(this).data('id');
    });
    */

    // THE CHECKBOXES
    function getCheckedBoxes() {
        var checkedBoxes = [];
        $("input[type=checkbox]:checked").each(function() {
                checkedBoxes.push($(this).data('id'));
        });
        return checkedBoxes;
    }

    var bgColor = '#c4f9ff';

    // CHECK A BOX
    $('input[type=checkbox]').on('click', function() {
        if ($(this).prop('checked')) {
            $(this).parent().parent().css('background-color', bgColor);
        } else {
            $(this).parent().parent().css('background-color', '');
        }
    });

    // SELECT ALL
    $('#selectAllBoxes').click(function() {
        $('input[type=checkbox]').prop('checked', true);
        $('input[type=checkbox]').parent().parent().css('background-color', bgColor);
        $('#advancedSelectOptions').show();
        $('#withSelected').show();
        // also disable pagination because this will select all even the hidden ones
        $('section.item:hidden').show();
        $('#loadAllButton').hide(); // hide load button when there is nothing more to show
        $('#loadButton').hide(); // hide load button when there is nothing more to show
    });

    // UNSELECT ALL
    $('#unselectAllBoxes').click(function() {
        $('input:checkbox').prop('checked', false);
        $('input[type=checkbox]').parent().parent().css('background-color', '');
    });

    // INVERT SELECTION
    $('#invertSelection').click(function() {
        $('input[type=checkbox]').each(function () {
            this.checked = !this.checked;
            if ($(this).prop('checked')) {
                $(this).parent().parent().css('background-color', bgColor);
            } else {
                $(this).parent().parent().css('background-color', '');
            }
        });
    });

    // hide the "with selected" block if no checkboxes are checked
    $('#withSelected').hide();
    // no need to show the unselect/invert links if no one is selected
    $('#advancedSelectOptions').hide();
    $('input[type=checkbox]').click(function() {
        $('#advancedSelectOptions').show();
        $('#withSelected').show();
    });

    // UPDATE THE STATUS/ITEM TYPE OF SELECTED BOXES ON SELECT CHANGE
    $('#catChecked').on('change', function() {
        var ajaxs = [];
        // get the item id of all checked boxes
        var checked = getCheckedBoxes();
        // loop on it and update the status/item type
        $.each(checked, function(index, value) {
            ajaxs.push($.post('app/controllers/EntityController.php', {
                updateCategory : true,
                id : value,
                categoryId : $('#catChecked').val(),
                type : $('#type').data('type')
            }));
        });
        // reload the page once it's done
        // a simple reload would not work here
        // we need to use when/then
        $.when.apply(null, ajaxs).then(function (){
            window.location.reload();
        });
    });

    // MAKE ZIP/CSV
    $('.csvzip').on('click', function() {
        // grey out the box to signal it has been clicked
        $(this).attr('disabled', 'disabled');
        // also display a wait text
        $(this).html('Please wait…');
        var type = $('#type').data('type');
        var checked = getCheckedBoxes();
        var what = $(this).data('what');
        window.location.href = 'make.php?what=' + what + '&type=' + type + '&id=' + checked.join('+');
    });

    // THE DELETE BUTTON FOR CHECKED BOXES
    $('#deleteChecked').on('click', function() {
        if (!confirm('Delete this?')) {
            return false;
        }
        // get the item id of all checked boxes
        var checked = getCheckedBoxes();
        // loop on it and delete stuff
        $.each(checked, function(index, value) {
            $.post('app/controllers/EntityController.php', {
                destroy: true,
                id: value,
                type: $('#type').data('type')
            });
            // hide the div
            $('#parent_' + value).hide(200);
        });
    });
});

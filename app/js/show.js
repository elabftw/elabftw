$(document).ready(function(){

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
        }).done(function(body) {
            $('#bodyToggle_' + id).html(body);
            $('#bodyToggle_' + id).toggle();

        });
    });

    // there is a create shortcut only for experiments
    var page = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
    if (page === 'experiments.php') {
        // KEYBOARD SHORTCUT
        key($('#shortcuts').data('create'), function(){
            location.href = 'app/controllers/ExperimentsController.php?create=true'
        });
    }

    // SHOW MORE BUTTON
    $('section.item').hide(); // hide everyone
    $('section.item').slice(0, $('#limit').data('limit')).show(); // show only the default at the beginning
    $('#loadButton').click(function(e){ // click to load more
        e.preventDefault();
        $('section.item:hidden').slice(0, $('#limit').data('limit')).show();
        if ($('section.item:hidden').length == 0) { // check if there are more exp to show
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
});

// ADVANCED SEARCH BAR (TOP RIGHT)
$('#adv_search').hide();
$('#big_search_input').click(function() {
    $('#adv_search').show();
});

// HELP CONTAINER
$('#help_container').hide();
$('#help').click(function() {
    $('#help_container').toggle();
});
$(document).on('click', '.helpClose', function() {
    $('#help_container').hide();
});

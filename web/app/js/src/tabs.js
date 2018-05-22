// TABS
// get the tab=X parameter in the url
var params = getGetParameters();
var tab = parseInt(params.tab, 10);
if (!isInt(tab)) {
    tab = 1;
}
var initdiv = '#tab' + tab + 'div';
var inittab = '#tab' + tab;
// init
$(".divhandle").hide();
$(initdiv).show();
$(inittab).addClass('selected');

$(".tabhandle" ).click(function(event) {
    var tabhandle = '#' + event.target.id;
    var divhandle = '#' + event.target.id + 'div';
    $(".divhandle").hide();
    $(divhandle).show();
    $(".tabhandle").removeClass('selected');
    $(tabhandle).addClass('selected');
});

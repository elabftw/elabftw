$(document).ready(function(){
	$(".toggle_container").hide();
	$("a.trigger").click(function(){
		$('.toggle_container').slideToggle("slow");
	});
});

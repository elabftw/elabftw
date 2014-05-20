/**
 * In-Line Text Editing
 * April 3, 2009
 * Corey Hart @ http://www.codenothing.com
 *
 * @href: Link to script for ajax call
 * @display: Displayed text grouping
 * @form: Form grouping
 * @text: Text input element (INPUT or TEXTAREA)
 * @save: Save Button
 * @cancel: Cancel Button
 * @loadtxt: html to replace display text with while ajax call completes
 * @hover: Class for mouseover/mouseout's on the display grouping
 */ 
;(function($){
	$.fn.inlineEdit = function(options){
		var text;
		var settings = $.extend({
			href: 'ajax.php',
			display: '.display',
			form: '.form',
			text: '.text',
			save: '.save',
			cancel: '.cancel',
			loadtxt: 'Loading...',
			hover: 'none-error-404'
		},options||{});

		this.each(function(){
			// Display Actions
			$(settings.display, this).mouseover(function(){
				$(this).addClass(settings.hover);
			}).mouseout(function(){
				$(this).removeClass(settings.hover);
			}).click(function(){
				text = $(this).html();
				$(this).hide().siblings(settings.form).show().find(settings.text).val(text).focus();
				return false;
			});

			// Cancel Actions
			$(settings.cancel, this).click(function(){
				$(this).parents(settings.form).hide().siblings(settings.display).show();
				return false;
			});

			// Save Actions
			$(settings.save, this).click(function(){
				text = $(this).parents(settings.form).find(settings.text).val();
				$(this).parents(settings.form).hide().siblings(settings.display).html(settings.loadtxt)
					.load(settings.href, {text: text}).show();
				return false;
			});
		});
	};
})(jQuery);

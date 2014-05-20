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
;(function(a){a.fn.inlineEdit=function(b){var d;var c=a.extend({href:"ajax.php",display:".display",form:".form",text:".text",save:".save",cancel:".cancel",loadtxt:"Loading...",hover:"none-error-404"},b||{});this.each(function(){a(c.display,this).mouseover(function(){a(this).addClass(c.hover);}).mouseout(function(){a(this).removeClass(c.hover);}).click(function(){d=a(this).html();a(this).hide().siblings(c.form).show().find(c.text).val(d).focus();return false;});a(c.cancel,this).click(function(){a(this).parents(c.form).hide().siblings(c.display).show();return false;});a(c.save,this).click(function(){d=a(this).parents(c.form).find(c.text).val();a(this).parents(c.form).hide().siblings(c.display).html(c.loadtxt).load(c.href,{text:d}).show();return false;});});};})(jQuery);

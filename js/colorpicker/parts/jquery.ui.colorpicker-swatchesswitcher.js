;jQuery(function($) {	
	$.colorpicker.parts.swatchesswitcher = function (inst) {
		var that = this,
			part = null;

		this.init	= function () {
			var names = $.map($.colorpicker.swatches, function(v, name) { return name; }).sort(),
				current = inst.options.swatches || 'html',
				select = $('<select>').width(inst.options.swatchesWidth + 2);

			part	= $('<div/>')
					.addClass('ui-colorpicker-swatchesswitcher')
					.css('text-align', 'center')
					.appendTo($('.ui-colorpicker-swatchesswitcher-container', inst.dialog));
			select.appendTo(part);
			
			$.each(names, function(x, name) {
				var label	= $.colorpicker.swatchesNames[name]
							|| name.replace(/[-_]/, ' ').replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function($1) {
									return $1.toUpperCase();
								});
				$('<option>').val(name).text(label).appendTo(select);
			});
			select.val(current);
			
			select.change(function() {
				inst.option('swatches', $(this).val());
			});
		};
		
		this.disable = function (disabled) {
			$('select', part).prop('disabled', disabled);
		};
	};
});

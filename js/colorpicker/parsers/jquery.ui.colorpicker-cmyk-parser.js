;jQuery(function($) {
	$.colorpicker.parsers['CMYK'] = function (color) {
		var m = /^cmyk\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/.exec(color);
		if (m) {
			return (new $.colorpicker.Color()).setCMYK(
				m[1] / 255,
				m[2] / 255,
				m[3] / 255,
				m[4] / 255
			);
		}
	};
});

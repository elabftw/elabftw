;jQuery(function($) {
	$.colorpicker.parsers['CMYK%'] = function (color) {
		var m = /^cmyk\(\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*\)$/.exec(color);
		if (m) {
			return (new $.colorpicker.Color()).setCMYK(
				m[1] / 100,
				m[2] / 100,
				m[3] / 100,
				m[4] / 100
			);
		}
	};
});

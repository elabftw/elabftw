/*
 * Helper functions for 3Dmol.js
 * https://www.elabftw.net
 */

// Remove all surfaces from div with given id and re-render
function remove_surfaces(id) {
    var view = $3Dmol.viewers[id];
    view.removeAllSurfaces();
    view.render();

}

// Show molecule as cartoon representation and re-render. Color can be passed
// optionally. If no color is given, spectrum is assumed.
function show_cartoon(id, color) {
    if (typeof color !== 'string') {
        color = 'spectrum';
    }

    var view = $3Dmol.viewers[id];
    view.setStyle(
                  {hetflag:false},
                  {cartoon:{color:color}}
                  );
    view.render();
}

// Show molecule as sticks and re-render
function show_stick(id) {
    var view = $3Dmol.viewers[id];
    view.setStyle(
                  {},
                  {stick:{}}
                  );
    view.render();
}

// Show molecule as surface. Takes optional opacity (float) and color
// (string in hex notation) as parameters. Opacity defaults to 1.
// If no valid color is passed, the surface is colored according to charges.
function show_surface(id, opacity, color) {
    var view = $3Dmol.viewers[id];
    if (typeof opacity !== 'number') {
        opacity = 1;
    }

    var style_scheme = '{opacity:' + opacity + ';';

    if (typeof color !== 'string') {
        view.addSurface($3Dmol.SurfaceType.MS, {opacity:opacity,map:{prop:'partialCharge',scheme:new $3Dmol.Gradient.RWB(-.6,.6)}}, {hetflag:false}, {hetflag:false});
    } else {
        view.addSurface($3Dmol.SurfaceType.MS, {opacity:opacity,color:color}, {hetflag:false}, {hetflag:false});
    }

    view.render();
}

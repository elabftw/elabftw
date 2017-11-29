/*
 * Helper functions for 3Dmol.js
 * https://www.elabftw.net
 */

/*
// Remove all surfaces from div with given id and re-render
$(document).on('click', '.rmSurface', function() {
    var id = $(this).data('divid');
    var view = $3Dmol.viewers[id];
    view.removeAllSurfaces();
    view.render();
});
*/

// Show molecule as cartoon representation and re-render. Color can be passed
// optionally. If no color is given, spectrum is assumed.
// CARTOON NOT WORKING
/*
$(document).on('click', '.3dmol-cartoon', function() {
    $3Dmol.viewers[$(this).data('divid')].setStyle({resn:'PMP',byres:true,expand:5},{cartoon:{}}).render();
    /*
    var id = $(this).data('divid');
    var view = $3Dmol.viewers[id];
    //view.setStyle({}, {cartoon:{}});
    view.addSurface($3Dmol.SurfaceType.VDW, {opacity:0.85,voldata: new $3Dmol.VolumeData(volumedata, "cube"), volscheme: new $3Dmol.Gradient.RWB(-10,10)},{});
    /*
    if (typeof color !== 'string') {
        color = 'spectrum';
    }

    view.setStyle({
        hetflag:false
    }, {
        cartoon: {
            color:'spectrum'
        }
    });
    view.render();
    */
/*
});
*/

// CROSS
$(document).on('click', '.3dmol-cross', function() {
    $3Dmol.viewers[$(this).data('divid')].setStyle({},{cross:{}}).render();
});

// LINE
$(document).on('click', '.3dmol-line', function() {
    $3Dmol.viewers[$(this).data('divid')].setStyle({},{line:{}}).render();
});

// SPHERE
$(document).on('click', '.3dmol-sphere', function() {
    $3Dmol.viewers[$(this).data('divid')].setStyle({},{sphere:{}}).render();
});

// STICK
$(document).on('click', '.3dmol-stick', function() {
    $3Dmol.viewers[$(this).data('divid')].setStyle({},{stick:{}}).render();
});

/*
$(document).on('click', '.3dmol-solid', function() {
    var viewer = $3Dmol.viewers[$(this).data('divid')];
    var voldata = new $3Dmol.VolumeData(viewer.pdbData(), "cube");
                     viewer.addIsosurface(voldata, {isoval: 0.01,
                                                                         color: "blue"});
                     viewer.addIsosurface(voldata, {isoval: -0.01,
                                                                         color: "red"});
                     viewer.zoomTo();
                     viewer.render();
    /*
    var id = $(this).data('divid');
    var color = '0xffffff';
    show_surface(id, 1, color);
    */
/*
});
$(document).on('click', '.3dmol-trans', function() {
    var id = $(this).data('divid');
    var color = '0xffffff';
    show_surface(id, 0.7, color);
});
// Show molecule as surface. Takes optional opacity (float) and color
// (string in hex notation) as parameters. Opacity defaults to 1.
// If no valid color is passed, the surface is colored according to charges.
function show_surface(id, opacity, color) {
    var id = $(this).data('divid');
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
/*
function remove_surfaces(id) {
    var view = $3Dmol.viewers[id];
    view.removeAllSurfaces();
    view.render();

}
*/

    /*
// Show molecule as cartoon representation and re-render. Color can be passed
// optionally. If no color is given, spectrum is assumed.
function show_cartoon(id, color) {
    if (typeof color !== 'string') {
        color = 'spectrum';
    }

    var view = $3Dmol.viewers[id];
    view.setStyle({
        hetflag:false
    }, {
        cartoon: {
            color:color
        }
    });
    view.render();
}
*/

/*
// Show molecule as sticks and re-render
function show_stick(id) {
    var view = $3Dmol.viewers[id];
    view.setStyle({},
    {
        stick:{}
    });
    view.render();
}
*/

// Show molecule as surface. Takes optional opacity (float) and color
// (string in hex notation) as parameters. Opacity defaults to 1.
// If no valid color is passed, the surface is colored according to charges.
/*
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
*/

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Cornify.js - because unicorns FTW
 */
let cornifyCount = 0;
const cornifyAdd = function() {
    cornifyCount += 1;
    const cornifyUrl = 'https://www.cornify.com/';
    let div = document.createElement('div');
    div.style.position = 'fixed';

    let numType = 'px';
    let heightRandom = Math.random()*0.75;
    let windowHeight = 768;
    let windowWidth = 1024;
    let height = 0;
    let de = document.documentElement;
    if (typeof(window.innerHeight) === 'number') {
        windowHeight = window.innerHeight;
        windowWidth = window.innerWidth;
    } else if(de && de.clientHeight) {
        windowHeight = de.clientHeight;
        windowWidth = de.clientWidth;
    } else {
        numType = '%';
        height = Math.round( height*100 )+'%';
    }

    div.onclick = cornifyAdd;
    div.style.zIndex = 10;
    div.style.outline = 0;

    if (cornifyCount === 15) {
        div.style.top = Math.max( 0, Math.round( (windowHeight-530)/2 ) )  + 'px';
        div.style.left = Math.round( (windowWidth-530)/2 ) + 'px';
        div.style.zIndex = 1000;
    } else {
        if(numType === 'px') {
            div.style.top = Math.round(windowHeight * heightRandom) + numType;
        } else {
            div.style.top = height;
        }
        div.style.left = Math.round( Math.random()*90 ) + '%';
    }

    let img = document.createElement('img');
    let currentTime = new Date();
    let submitTime = currentTime.getTime();
    if (cornifyCount === 15) submitTime = 0;
    img.setAttribute('src', cornifyUrl + 'getacorn.php?r=' + submitTime);
    let ease = "all .1s linear";
    div.style.WebkitTransition = ease;
    div.style.WebkitTransform = "rotate(1deg) scale(1.01,1.01)";
    div.style.transition = "all .1s linear";
    div.onmouseover = function() {
        let size = 1+Math.round(Math.random()*10)/100;
        let angle = Math.round(Math.random()*20-10);
        let result = "rotate("+angle+"deg) scale("+size+","+size+")";
        this.style.transform = result;
        this.style.WebkitTransform = result;
    };
    div.onmouseout = function() {
        let size = 0.9+Math.round(Math.random()*10)/100;
        let angle = Math.round(Math.random()*6-3);
        let result = "rotate("+angle+"deg) scale("+size+","+size+")";
        this.style.transform = result;
        this.style.WebkitTransform = result;
    };
    let body = document.getElementsByTagName('body')[0];
    body.appendChild(div);
    div.appendChild(img);

    // Add stylesheet
    if (cornifyCount === 1) {
        let cssExisting = document.getElementById('__cornify_css');
        if (!cssExisting) {
            let head = document.getElementsByTagName("head")[0];
            let css = document.createElement('link');
            css.id = '__cornify_css';
            css.type = 'text/css';
            css.rel = 'stylesheet';
            css.href = 'assets/cornify.min.css';
            css.media = 'screen';
            head.appendChild(css);
        }
        cornifyReplace();
    }
};

// Add magical text in h1 to h6 elements
const cornifyReplace = function() {
    let hc = 6;
    let hs;
    let h;
    let k;
    const words = ['Happy','Sparkly','Glittery','Fun','Magical','Lovely','Cute','Charming','Amazing','Wonderful'];
    while(hc >= 1) {
        hs = document.getElementsByTagName('h' + hc);
        for (k = 0; k < hs.length; k++) {
            h = hs[k];
            h.innerHTML = words[Math.floor(Math.random()*words.length)] + ' ' + h.innerHTML;
        }
        hc-=1;
    }
};

/*
* Adapted from http://www.snaptortoise.com/konami-js/ (dead link)
* This listens for the Konami code and adds a unicorn if it is typed
*/
let cornami = {
    input: "",
    pattern: "38384040373937396665",
    clear: function() {
        const _this = this;
        setTimeout(_this.clearInput(), 5000);
    },
    load: function() {
        window.document.onkeydown = function(e) {
            if (cornami.input === cornami.pattern) {
                cornifyAdd();
                clearTimeout(cornami.clear);
                return;
            } else {
                cornami.input += e ? e.keyCode : event.keyCode;
                if (cornami.input === cornami.pattern) {
                    cornifyAdd();
                }
                clearTimeout(cornami.clear);
                const _this = this;
                cornami.clear = _this.clear();
            }
        };
    },
    clearInput: function() {
        cornami.input = "";
        clearTimeout(cornami.clear);
    }
};
cornami.load();

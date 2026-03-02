// keymaster.js library
// Original code (c) 2011-2013 Thomas Fuchs
// License MIT
// Modified by Nicolas CARPi for eLabFTW

let k;
const _handlers = {};
const _mods = { 16: false, 18: false, 17: false, 91: false };
// modifier keys
const _MODIFIERS = {
  '⇧': 16, shift: 16,
  '⌥': 18, alt: 18, option: 18,
  '⌃': 17, ctrl: 17, control: 17,
  '⌘': 91, command: 91,
};
// special keys
const specialKeysMap = {
  backspace: 8, tab: 9, clear: 12,
  enter: 13, 'return': 13,
  esc: 27, escape: 27, space: 32,
  left: 37, up: 38,
  right: 39, down: 40,
  del: 46, 'delete': 46,
  home: 36, end: 35,
  pageup: 33, pagedown: 34,
  ',': 188, '.': 190, '/': 191,
  '`': 192, '-': 189, '=': 187,
  ';': 186, '\'': 222,
  '[': 219, ']': 221, '\\': 220,
};

const code = function(x) {
  return specialKeysMap[x] || x.toUpperCase().charCodeAt(0);
};
const _downKeys = [];

for (k=1;k<20;k++) specialKeysMap['f'+k] = 111+k;

// abstract key logic for assign and unassign
function getKeys(key) {
  // remove all whitespaces
  key = key.replace(/\s/g, '');
  const keys = key.split(',');
  if ((keys[keys.length - 1]) == '') {
    keys[keys.length - 2] += ',';
  }
  return keys;
}

// abstract mods logic for assign and unassign
function getMods(key) {
  const mods = key.slice(0, key.length - 1);
  for (let mi = 0; mi < mods.length; mi++)
    mods[mi] = _MODIFIERS[mods[mi]];
  return mods;
}

// parse and assign shortcut
function assignKey(key, method){
  let mods;
  const keys = getKeys(key);

  // for each shortcut
  for (let i = 0; i < keys.length; i++) {
    // set modifier keys if any
    mods = [];
    key = keys[i].split('+');
    if (key.length > 1){
      mods = getMods(key);
      key = [key[key.length-1]];
    }
    // convert to keycode and...
    key = key[0];
    key = code(key);
    // ...store handler
    if (!(key in _handlers)) _handlers[key] = [];
    _handlers[key].push({ shortcut: keys[i], method: method, key: keys[i], mods: mods });
  }
};

const modifierMap = {
  16:'shiftKey',
  18:'altKey',
  17:'ctrlKey',
  91:'metaKey',
};
function updateModifierKey(event: KeyboardEvent) {
  for (k in _mods) _mods[k] = event[modifierMap[k]];
};

// handle keydown event
function dispatch(event: KeyboardEvent) {
  let key, handler, k, i, modifiersMatch;
  key = event.keyCode;
  if (_downKeys.indexOf(key) === -1) {
    _downKeys.push(key);
  }

  // if a modifier key, set the key.<modifierkeyname> property to true and return
  if (key == 93 || key == 224) key = 91; // right command on webkit, command on Gecko
  if (key in _mods) {
    _mods[key] = true;
    // 'assignKey' from inside this closure is exported to window.key
    for (k in _MODIFIERS) if (_MODIFIERS[k] == key) assignKey[k] = true;
    return;
  }
  updateModifierKey(event);

  // see if we need to ignore the keypress (filter() can can be overridden)
  // by default ignore key presses if a select, textarea, or input is focused
  if (!assignKey.filter.call(this, event)) return;

  // abort if no potentially matching shortcuts found
  if (!(key in _handlers)) return;

  // for each potential shortcut
  for (i = 0; i < _handlers[key].length; i++) {
    handler = _handlers[key][i];

    // check if modifiers match if any
    modifiersMatch = handler.mods.length > 0;
    for (k in _mods)
      if ((!_mods[k] && handler.mods.indexOf(+k) > -1) ||
        (_mods[k] && handler.mods.indexOf(+k) == -1)) modifiersMatch = false;
    // call the handler and stop the event if necessary
    if ((handler.mods.length == 0 && !_mods[16] && !_mods[18] && !_mods[17] && !_mods[91]) || modifiersMatch){
      if (handler.method(event, handler) === false){
        event.preventDefault();
        event.stopPropagation();
      }
    }
  }
};

// unset modifier keys on keyup
function clearModifier(event: KeyboardEvent){
  let key = event.keyCode;
  let k;
  const i = _downKeys.indexOf(key);

  // remove key from _downKeys
  if (i >= 0) {
    _downKeys.splice(i, 1);
  }

  if (key == 93 || key == 224) key = 91;
  if (key in _mods) {
    _mods[key] = false;
    for (k in _MODIFIERS) if (_MODIFIERS[k] == key) assignKey[k] = false;
  }
};

function resetModifiers() {
  for (k in _mods) _mods[k] = false;
  for (k in _MODIFIERS) assignKey[k] = false;
};

function filter(event){
  const target = event.target as HTMLElement;
  if (!(target instanceof HTMLElement)) {
    return true;
  }
  const tagName = target.tagName;
  // ignore keypressed in any elements that support keyboard data input
  return !(tagName === 'INPUT' || tagName === 'SELECT' || tagName === 'TEXTAREA' || target.hasAttribute('contenteditable'));
}

// initialize key.<modifier> to false
for (k in _MODIFIERS) assignKey[k] = false;

// set the handlers globally on document
document.addEventListener('keydown', event => dispatch(event));
document.addEventListener('keyup', event => clearModifier(event));

// reset modifiers to false whenever the window is (re)focused.
window.addEventListener('focus', () => resetModifiers());

// set window.key and window.key.set/get/deleteScope, and the default filter
assignKey.filter = filter;

export { assignKey };

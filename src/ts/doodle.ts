/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import i18next from 'i18next';

$(document).ready(function() {
  if ($('#info').data('page') !== 'edit') {
    return;
  }
  // store the clicks
  let clickX = [];
  let clickY = [];
  // bool to store the state of painting
  let isPainting;
  const context: CanvasRenderingContext2D = (document.getElementById('doodleCanvas') as HTMLCanvasElement).getContext('2d');

  function draw(dragging: boolean): void {
    // get last items in arrays
    const x = clickX[clickX.length - 1];
    const y = clickY[clickY.length - 1];

    const path = new Path2D();

    if (dragging) {
      path.moveTo(clickX[clickX.length - 2], clickY[clickY.length - 2]);
    } else {
      // if it's just a point click, draw from close location
      path.moveTo(x - 1, y);
    }
    path.lineTo(x, y);
    path.closePath();

    if ($('#doodleEraser').is(':checked')) {
      context.globalCompositeOperation = 'destination-out';
      context.strokeStyle = 'rgba(0,0,0,1)';
    } else {
      context.globalCompositeOperation = 'source-over';
      context.strokeStyle = $('#doodleStrokeStyle').val() as string;
    }
    context.lineJoin = 'round';
    context.lineWidth = $('#doodleStrokeWidth').val() as number;

    context.stroke(path);
  }

  function addText(x: number, y: number, text: string): void {
    context.font = '18px Arial';
    context.fillStyle = $('#doodleStrokeStyle').val() as string;
    context.fillText(text, x, y);
  }

  function addClick(x: number, y: number, dragging: boolean): void {
    clickX.push(x);
    clickY.push(y);
    draw(dragging);
  }

  $('#doodle-anchor').on('click', '.clearCanvas', function() {
    context.clearRect(0, 0, context.canvas.width, context.canvas.height);
    clickX = [];
    clickY = [];
  });

  $('#doodle-anchor').on('click', '.saveCanvas', function() {
    const image = ($('#doodleCanvas')[0] as HTMLCanvasElement).toDataURL();
    let type = $(this).data('type');
    const id = $(this).data('id');
    const realName = prompt(i18next.t('request-filename'));
    if (realName == null) {
      return;
    }
    $.post('app/controllers/EntityAjaxController.php', {
      addFromString: true,
      type: type,
      realName: realName,
      id: id,
      fileType: 'png',
      string: image
    }).done(function(json) {
      if (type === 'items') {
        type = 'database';
      }
      $('#filesdiv').load(type + '.php?mode=edit&id=' + id + ' #filesdiv');
      notif(json);
    });
  });

  $('#doodleCanvas').mousedown(function(e) {
    // if ctrl key is pressed, we ask for text to insert
    if (e.ctrlKey) {
      const text = prompt('Text to insert:');
      if (text === null) {
        return;
      }
      addText(e.pageX - this.offsetLeft, e.pageY - this.offsetTop, text);
    } else {
      isPainting = true;
      addClick(e.pageX - this.offsetLeft, e.pageY - this.offsetTop, false);
    }
  });

  $('#doodleCanvas').mousemove(function(e) {
    if (isPainting) {
      addClick(e.pageX - this.offsetLeft, e.pageY - this.offsetTop, true);
    }
  });

  $('#doodleCanvas').mouseup(function() {
    isPainting = false;
  });

  $('#doodleCanvas').mouseleave(function() {
    isPainting = false;
  });

  const doodleCanvas = document.getElementById('doodleCanvas') as HTMLCanvasElement;
  doodleCanvas.addEventListener('touchstart', function(e) {
    const rect = this.getBoundingClientRect();
    const touch = e.touches[0];
    isPainting = true;
    addClick(touch.clientX - rect.left, touch.clientY - rect.top, false);
  }, false);

  doodleCanvas.addEventListener('touchmove', function(e) {
    if (isPainting) {
      const rect = this.getBoundingClientRect();
      const touch = e.touches[0];
      addClick(touch.clientX - rect.left, touch.clientY - rect.top, true);
    }
  }, false);

  doodleCanvas.addEventListener('touchend', function() {
    isPainting = false;
  }, false);

  doodleCanvas.addEventListener('touchcancel', function() {
    isPainting = false;
  }, false);

  doodleCanvas.addEventListener('touchstart', function (e) {
    e.preventDefault();
  }, false);

  doodleCanvas.addEventListener('touchend', function (e) {
    e.preventDefault();
  }, false);

  doodleCanvas.addEventListener('touchmove', function (e) {
    e.preventDefault();
  }, false);
});

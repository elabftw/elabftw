/**
 * doodle.js - for drawing with the mouse
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  $(document).ready(function() {
    // store the clicks
    let clickX = [];
    let clickY = [];
    // bool to store the state of painting
    let isPainting;
    const context = document.getElementById('doodleCanvas').getContext('2d');

    $('.canvasDiv').hide();
    $(document).on('click', '.clearCanvas', function() {
      context.clearRect(0, 0, context.canvas.width, context.canvas.height);
      clickX = [];
      clickY = [];
    });

    $(document).on('click', '.saveCanvas', function() {
      const image = ($('#doodleCanvas')[0]).toDataURL();
      let type = $(this).data('type');
      const id = $(this).data('id');
      const realName = prompt('Enter name of the file');
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
        $('#filesdiv').load(type + '.php?mode=edit&id=' + id + ' #filesdiv', function() {
          makeEditableFileComment();
        });
        notif(json);
      });
    });

    $('#doodleCanvas').mousedown(function(e) {
      // if ctrl key is pressed, we ask for text to insert
      if (e.ctrlKey) {
        var text = prompt('Text to insert:');
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

    function addText(x, y, text) {
      context.font = '18px Arial';
      context.fillStyle = $('#doodleStrokeStyle').val();
      context.fillText(text, x, y);
    }

    function addClick(x, y, dragging) {
      clickX.push(x);
      clickY.push(y);
      draw(dragging);
    }

    function draw(dragging) {
      // get last items in arrays
      let x = clickX[clickX.length - 1];
      let y = clickY[clickY.length - 1];

      let path = new Path2D();

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
        context.strokeStyle = $('#doodleStrokeStyle').val();
      }
      context.lineJoin = 'round';
      context.lineWidth = $('#doodleStrokeWidth').val();

      context.stroke(path);
    }
  });
}());

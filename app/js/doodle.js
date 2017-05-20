$(document).ready(function() {
    $('.canvasDiv').hide();
    $(document).on('click', '.toggleCanvas', function() {
        $('.canvasDiv').toggle();
    });
    $(document).on('click', '.clearCanvas', function() {
        context.clearRect(0, 0, context.canvas.width, context.canvas.height);
        clickX = [];
        clickY = [];
        clickDrag = [];
    });

    $(document).on('click', '.saveCanvas', function() {
        var image = ($('#doodleCanvas')[0]).toDataURL();
        var type = $(this).data('type');
        var id = $(this).data('id');
        $.post('app/controllers/EntityController.php', {
            addFromString: true,
            type: type,
            id: id,
            fileType: 'png',
            string: image
        }).done(function() {
            if (type === 'items') {
                type = 'database';
            }
            $("#filesdiv").load(type + ".php?mode=edit&id=" + id + " #filesdiv");
            notif('Saved', 'ok');
        });
    });



    // code from http://www.williammalone.com/articles/create-html5-canvas-javascript-drawing-app/
    var clickX = [];
    var clickY = [];
    var clickDrag = [];
    var paint;
    context = document.getElementById('doodleCanvas').getContext("2d");

    $('#doodleCanvas').mousedown(function(e){
      paint = true;
      addClick(e.pageX - this.offsetLeft, e.pageY - this.offsetTop);
      redraw();
    });
    $('#doodleCanvas').mousemove(function(e){
          if(paint){
                  addClick(e.pageX - this.offsetLeft, e.pageY - this.offsetTop, true);
                  redraw();
                }
    });
    $('#doodleCanvas').mouseup(function(){
          paint = false;
    });
    $('#doodleCanvas').mouseleave(function(){
          paint = false;
    });

    function addClick(x, y, dragging)
    {
        clickX.push(x);
        clickY.push(y);
        clickDrag.push(dragging);
    }
    function redraw() {
        context.clearRect(0, 0, context.canvas.width, context.canvas.height);

        context.strokeStyle = "#29AEB9";
        context.lineJoin = "round";
        context.lineWidth = 5;

        for(var i=0; i < clickX.length; i++) {
        context.beginPath();
        if(clickDrag[i] && i){
          context.moveTo(clickX[i-1], clickY[i-1]);
         }else{
           context.moveTo(clickX[i]-1, clickY[i]);
         }
         context.lineTo(clickX[i], clickY[i]);
         context.closePath();
         context.stroke();
        }
    }
});

context = document.getElementById('doodleCanvas').getContext("2d");
$('#doodleCanvas').mousedown(function(e){
  var mouseX = e.pageX - this.offsetLeft;
  var mouseY = e.pageY - this.offsetTop;
		
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
$('#doodleCanvas').mouseup(function(e){
      paint = false;
});
$('#doodleCanvas').mouseleave(function(e){
      paint = false;
});

var clickX = new Array();
var clickY = new Array();
var clickDrag = new Array();
var paint;

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

function clearCanvas() {
    context.clearRect(0, 0, context.canvas.width, context.canvas.height);
    clickX = [];
    clickY = [];
    clickDrag = [];
}

function saveCanvas(id) {
    var image = ($('#doodleCanvas')[0]).toDataURL();
    $.post('app/controllers/ExperimentsController.php', {
        addFromString: true,
        id: id,
        type: 'png',
        string: image
    }).done(function() {
        $("#filesdiv").load("experiments.php?mode=edit&id=" + id + " #filesdiv");
    });

}


// Select an element that has the mission-graph class applied
var elementWithClass = document.querySelector(".mission-graph");

// Get the computed styles for that element
var style = getComputedStyle(elementWithClass);

// Get amount
var amount = !isNaN(document.getElementById("missiongraph_canvas").dataset.amount) ? document.getElementById("missiongraph_canvas").dataset.amount : 0;

/* SETTINGS
 ************************************************** */
const graph_settings = {
    canvasId: "missiongraph_canvas", // ID of mission graph canvas DOM element
    fillAmount: amount, // Actual amount to fill graph stored in canvas element data-amount attribute
    baseAmount: 100, // Max amount for donut
    fontColor: style.getPropertyValue("color").trim(), // Color of active donut
    fillColor: style.getPropertyValue("accent-color").trim(), // Color of active donut
    baseColor: style.getPropertyValue("outline-color").trim(), // Color of base donut
    lineWidth: 15, // Thickness of donut
    fontSize: "25px",
    fontFace: "Arial",
    scaleFactor: 5,
};

/* graph_canvas
************************************************** */
const graph_canvas = document.getElementById(graph_settings.canvasId);
graph_canvas.style.letterSpacing = '-0.4';
graph_canvas.halfWidth = graph_canvas.width / 2;
graph_canvas.halfHeight = graph_canvas.height / 2;
graph_canvas.style.width = graph_canvas.style.width || graph_canvas.width + 'px';
graph_canvas.style.height = graph_canvas.style.height || graph_canvas.height + 'px';
// increase dpi of graph_canvas

// Resize graph_canvas and scale future draws.
graph_canvas.width = Math.ceil(graph_canvas.width * graph_settings.scaleFactor);
graph_canvas.height = Math.ceil(graph_canvas.height * graph_settings.scaleFactor);
var graph_ctx = graph_canvas.getContext('2d');
graph_ctx.scale(graph_settings.scaleFactor, graph_settings.scaleFactor);

draw(drawContinuousCircle);

/* DRAW
************************************************** */
function draw (graphDrawingFunction) {
    graph_ctx.clearRect(0, 0, graph_canvas.width, graph_canvas.height);
    graph_ctx.lineWidth = graph_settings.lineWidth;

    graphDrawingFunction();
    drawText();
}

function drawContinuousCircle() {
    var endAngle = (graph_settings.fillAmount / 100) * (Math.PI * 2) - (Math.PI / 2);
    var lineWidth = 8;

    graph_ctx.beginPath();
    graph_ctx.arc(50, 50, 40, 0, 2 * Math.PI);
    graph_ctx.lineWidth = lineWidth;
    graph_ctx.strokeStyle = graph_settings.baseColor;
    graph_ctx.stroke();

    graph_ctx.beginPath();
    graph_ctx.arc(50, 50, 40, -Math.PI / 2, endAngle);
    graph_ctx.lineWidth = lineWidth;
    graph_ctx.strokeStyle = graph_settings.fillColor;
    graph_ctx.stroke();
}

function drawDashedCircle() {
    var pointArray= calcPointsCirc(50,50,40, 0.15);
    var numberOfFilledLines = Math.ceil(pointArray.length / 100 * graph_settings.fillAmount);
    graph_ctx.strokeStyle = graph_settings.fillColor;
    graph_ctx.beginPath();

    for(p = 0; p < pointArray.length; p++){
        if (p === numberOfFilledLines) {
            graph_ctx.beginPath();
            graph_ctx.strokeStyle = graph_settings.baseColor;
        }
        graph_ctx.moveTo(pointArray[p].x, pointArray[p].y);
        graph_ctx.lineTo(pointArray[p].ex, pointArray[p].ey);

        graph_ctx.stroke();
    }
    graph_ctx.closePath();
}

/* misc */

function calcPointsCirc( cx,cy, rad, dashLength)
{
    var n = rad/dashLength,
        alpha = Math.PI * 2 / n,
        pointObj = {},
        points = [],
        i = -0;

    while( i < n )
    {
        var adjustingDegrees = -Math.PI / 2;
        var theta = alpha * i + adjustingDegrees,
            theta2 = alpha * (i+1) + adjustingDegrees;

        points.push({
            x : (Math.cos(theta2) * rad) + cx,
            y : (Math.sin(theta2) * rad) + cy,
            ex : (Math.cos(theta) * rad) + cx,
            ey : (Math.sin(theta) * rad) + cy
        });
        i+=2;
    }
    return points;
}

function drawText (){
    graph_ctx.fillStyle = graph_settings.fontColor;
    graph_ctx.font = '' + graph_settings.fontSize + ' ' + graph_settings.fontFace;
    graph_ctx.textAlign = 'center';
    graph_ctx.fillText(
        graph_settings.fillAmount+'%',
        graph_canvas.halfWidth,
        graph_canvas.halfHeight + (parseInt(graph_settings.fontSize) / 3)
    );
}

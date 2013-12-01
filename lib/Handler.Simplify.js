/*
 * Simplifying the polyline layer.
 */

if( !('mapBBCodeHandlers' in window) )
	window.mapBBCodeHandlers = [];

window.mapBBCodeHandlers.push({
    steps: 8,
    firstStep: 0.0002,
    multiplier: 2.0,

    applicableTo: function( layer ) {
        return layer instanceof L.Polyline && !(layer instanceof L.Polygon);
    },

    createEditorPanel: function( layer, ui ) {
        layer.s_nodes = layer.s_initial = layer.getLatLngs();
        
        function updateNodes( nodes ) {
            if( nodes )
                layer.s_nodes = nodes;
            layer.setLatLngs(layer.s_nodes);
            layer.editing.updateMarkers();
        }
        
        var toleranceDiv = document.createElement('div');
        toleranceDiv.style.whiteSpace = 'nowrap';
        toleranceDiv.appendChild(document.createTextNode((ui.strings.simplify || 'Simplify') + ': '));
        var tolerance = 0.0, td = this.firstStep, rid = 'tol' + L.stamp(this), i;
        for( i = 0; i < this.steps; i++ ) {
            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = rid;
            radio.style.margin = '3px 0';
            radio.tolerance = tolerance;
            radio.title = tolerance;
            if( i == 0 )
                radio.checked = true;
            tolerance = td;
            td *= this.multiplier;
            radio.onclick = function(e) {
                var target = (window.event && window.event.srcElement) || e.target || e.srcElement,
                    tolerance = target.tolerance;
                updateNodes(tolerance ? simplify(layer.s_initial, tolerance, true) : layer.s_initial);
            }
            toleranceDiv.appendChild(radio);
        }
        
        var resetDiv = document.createElement('div');
        resetDiv.style.display = 'none';
        var resetBtn = document.createElement('input');
        resetBtn.type = 'button';
        resetBtn.value = ui.strings.revertChanges || 'Revert changes';
        resetBtn.onclick = function() {
            toleranceDiv.style.display = 'block';
            resetDiv.style.display = 'none';
            updateNodes();
        };
        resetDiv.appendChild(resetBtn);

        layer.on('edit', function() {
            toleranceDiv.style.display = 'none';
            resetDiv.style.display = 'block';
        });
        
        var simplifyDiv = document.createElement('div');
        simplifyDiv.style.marginBottom = '4px';
        simplifyDiv.appendChild(toleranceDiv);
        simplifyDiv.appendChild(resetDiv);
        return simplifyDiv;
    }
});

/*
 (c) 2013, Vladimir Agafonkin
 Simplify.js, a high-performance JS polyline simplification library
 mourner.github.io/simplify-js
 L.point was converted to L.latLng
*/

(function () { "use strict";

// square distance between 2 points
function getSqDist(p1, p2) {

    var dx = p1.lng - p2.lng,
        dy = p1.lat - p2.lat;

    return dx * dx + dy * dy;
}

// square distance from a point to a segment
function getSqSegDist(p, p1, p2) {

    var x = p1.lng,
        y = p1.lat,
        dx = p2.lng - x,
        dy = p2.lat - y;

    if (dx !== 0 || dy !== 0) {

        var t = ((p.lng - x) * dx + (p.lat - y) * dy) / (dx * dx + dy * dy);

        if (t > 1) {
            x = p2.lng;
            y = p2.lat;

        } else if (t > 0) {
            x += dx * t;
            y += dy * t;
        }
    }

    dx = p.lng - x;
    dy = p.lat - y;

    return dx * dx + dy * dy;
}
// rest of the code doesn't care about point format

// basic distance-based simplification
function simplifyRadialDist(points, sqTolerance) {

    var prevPoint = points[0],
        newPoints = [prevPoint],
        point;

    for (var i = 1, len = points.length; i < len; i++) {
        point = points[i];

        if (getSqDist(point, prevPoint) > sqTolerance) {
            newPoints.push(point);
            prevPoint = point;
        }
    }

    if (prevPoint !== point) {
        newPoints.push(point);
    }

    return newPoints;
}

// simplification using optimized Douglas-Peucker algorithm with recursion elimination
function simplifyDouglasPeucker(points, sqTolerance) {

    var len = points.length,
        MarkerArray = typeof Uint8Array !== 'undefined' ? Uint8Array : Array,
        markers = new MarkerArray(len),
        first = 0,
        last = len - 1,
        stack = [],
        newPoints = [],
        i, maxSqDist, sqDist, index;

    markers[first] = markers[last] = 1;

    while (last) {

        maxSqDist = 0;

        for (i = first + 1; i < last; i++) {
            sqDist = getSqSegDist(points[i], points[first], points[last]);

            if (sqDist > maxSqDist) {
                index = i;
                maxSqDist = sqDist;
            }
        }

        if (maxSqDist > sqTolerance) {
            markers[index] = 1;
            stack.push(first, index, index, last);
        }

        last = stack.pop();
        first = stack.pop();
    }

    for (i = 0; i < len; i++) {
        if (markers[i]) {
            newPoints.push(points[i]);
        }
    }

    return newPoints;
}

// both algorithms combined for awesome performance
function simplify(points, tolerance, highestQuality) {

    var sqTolerance = tolerance !== undefined ? tolerance * tolerance : 1;

    points = highestQuality ? points : simplifyRadialDist(points, sqTolerance);
    points = simplifyDouglasPeucker(points, sqTolerance);

    return points;
}

window.simplify = simplify;

})();

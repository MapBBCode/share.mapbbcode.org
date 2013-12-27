<?php

class HTMLFormat implements Format {
    public $title = 'HTML';
    public $mime = 'text/html';

    public function export( $data ) {
        global $formats;
        $geojson = isset($formats['geojson']) ? $formats['geojson']->export($data) : '';
        if( strlen($geojson) == 0 )
            return '';
        $title = isset($data['title']) ? htmlspecialchars($data['title']) : 'MapBBCode';
		$cnt = isset($data['objs']) && $data['objs'] ? count($data['objs']) : 0;
		if( $cnt == 0 )
			$fitBounds = isset($data['zoom']) && isset($data['pos']) ? 'map.setView(['.$data['pos'][0].', '.$data['pos'][1].'], '.$data['zoom'].');' : 'map.setView([55, 19], 5);';
		elseif( $cnt == 1 && isset($data['objs'][0]['coords']) && count($data['objs'][0]['coords']) == 1 ) {
			$c = $data['objs'][0]['coords'][0];
			$fitBounds = 'map.setView(['.$c[0].', '.$c[1].'], 16);';
		} else
			$fitBounds = 'map.fitBounds(layer.getBounds());';
        $html = <<<HTMLE
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.css" />
<script src="http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.js"></script>
<script>
// this is a special type of icon. You can remove this class if you don't need it
L.PopupIcon = L.Icon.extend({
    initialize: function( text, options ) {
        L.Icon.prototype.initialize.call(this, options);
        this._text = text;
    },

    createIcon: function() {
        var pdiv = document.createElement('div'),
            div = document.createElement('div'),
            width = 150;

        pdiv.style.position = 'absolute';
        div.style.position = 'absolute';
        div.style.width = width + 'px';
        div.style.bottom = '-3px';
        div.style.pointerEvents = 'none';
        div.style.left = (-width / 2) + 'px';
        div.style.margin = div.style.padding = '0';
        pdiv.style.margin = pdiv.style.padding = '0';

        var contentDiv = document.createElement('div');
        contentDiv.innerHTML = this._text;
        contentDiv.style.textAlign = 'center';
        contentDiv.style.lineHeight = '1.2';
        contentDiv.style.backgroundColor = 'white';
        contentDiv.style.boxShadow = '0px 1px 10px rgba(0, 0, 0, 0.655)';
        contentDiv.style.padding = '4px 7px';
        contentDiv.style.borderRadius = '5px';
        contentDiv.style.margin = '0 auto';
        contentDiv.style.display = 'table';
        contentDiv.style.pointerEvents = 'auto';

        var stop = L.DomEvent.stopPropagation;
        L.DomEvent
            .on(contentDiv, 'click', stop)
            .on(contentDiv, 'mousedown', stop)
            .on(contentDiv, 'dblclick', stop);

        var tipcDiv = document.createElement('div');
        tipcDiv.className = 'leaflet-popup-tip-container';
        tipcDiv.style.width = '20px';
        tipcDiv.style.height = '11px';
        tipcDiv.style.padding = '0';
        tipcDiv.style.margin = '0 auto';
        var tipDiv = document.createElement('div');
        tipDiv.className = 'leaflet-popup-tip';
        tipDiv.style.width = tipDiv.style.height = '8px';
        tipDiv.style.marginTop = '-5px';
        tipDiv.style.boxShadow = 'none';
        tipcDiv.appendChild(tipDiv);

        div.appendChild(contentDiv);
        div.appendChild(tipcDiv);
        pdiv.appendChild(div);
        return pdiv;
    },

    createShadow: function () {
        return null;
    }
});
</script>
<style>
html, body, #map { margin: 0; height: 100%; }
#title {
    position: absolute;
    width: 500px;
    min-width: 300px;
    margin: 0 auto;
    left: 0; right: 0;
    top: 10px;
    padding: 6px;
    border-radius: 6px;
    background-color: white;
    opacity: 0.9;
    text-align: center;
    font-family: Arial, sans-serif;
    display: none;
}
</style>
</head>
<body>
<div id="map"></div>
<div id="title"></div>
<script>
var map = L.map('map');
L.tileLayer('http://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
    minZoom: 0, maxZoom: 19
}).addTo(map);

var data = $geojson;

if( data.title ) {
    var t = document.getElementById('title');
    t.innerHTML = data.title;
    t.style.display = 'block';
}

var layer = L.geoJson(data, {
    style: function( feature ) {
        var style = {};
        if( feature.properties.color)
            style.color = feature.properties.color;
        if( feature.geometry.type == 'Polygon' ) {
            style.weight = 3;
            style.opacity = 0.7;
            style.fill = true;
            style.fillOpacity = 0.1;
        } else if( feature.geometry.type == 'LineString' ) {
            style.weight = 5;
            style.opacity = 0.7;
        }
        return style;
    },
    onEachFeature: function( feature, layer ) {
        var title = feature.properties.title;
        if( title ) {
            if( layer instanceof L.Marker && title.length <= 30 ) {
                layer.setIcon(new L.PopupIcon(title));
                layer.options.clickable = false;
            } else
                layer.bindPopup(title);
        } else
            layer.options.clickable = false;
    }
}).addTo(map);
$fitBounds
</script>
</body>
</html>
HTMLE;
        return $html;
    }

    public function test( $header ) {
        return false;
    }

    public function import( $file ) {
    }
}

$formats['html'] = new HTMLFormat();

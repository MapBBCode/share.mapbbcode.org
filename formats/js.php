<?php

class JSFormat implements Format {
    public $mime = 'text/html';

    public function export( $data ) {
        $title = isset($data['title']) ? htmlspecialchars($data['title']) : 'MapBBCode';
		$cnt = isset($data['objs']) && $data['objs'] ? count($data['objs']) : 0;
		if( $cnt == 0 )
			$fitBounds = isset($data['zoom']) && isset($data['pos']) ? 'map.setView(['.$data['pos'][0].', '.$data['pos'][1].'], '.$data['zoom'].');' : 'map.setView([55, 19], 5);';
		elseif( $cnt == 1 && isset($data['objs'][0]['coords']) && count($data['objs'][0]['coords']) == 1 ) {
			$c = $data['objs'][0]['coords'][0];
			$fitBounds = 'map.setView(['.$c[0].', '.$c[1].'], 16);';
		} else
			$fitBounds = 'map.fitBounds(layer.getBounds());';
		$features = '';
		foreach( $data['objs'] as $obj ) {
            $coords = $obj['coords'];
            $cnt = count($coords);
            if( $cnt == 1 ) {
                $code = 'L.marker('.json_encode($coords[0]).')';
            } elseif( $cnt > 1 ) {
                if( $coords[0][0] == $coords[$cnt-1][0] && $coords[0][1] == $coords[$cnt-1][1] ) {
                    $code = 'L.polygon('.json_encode($coords).')';
                } else {
					$code = 'L.polyline('.json_encode($coords).')';
                }
            } else
                continue;
			if( isset($obj['text']) && strlen($obj['text']) > 0 )
				$code .= '.bindPopup('.json_encode($obj['text']).')';
			$features .= $code.".addTo(layer);\n";
		}
        $html = <<<HTMLE
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.css" />
<script src="http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.js"></script>
<style>html, body, #map { margin: 0; height: 100%; }</style>
</head>
<body>
<div id="map"></div>
<script>
var map = L.map('map');
L.tileLayer('http://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
    minZoom: 0, maxZoom: 19
}).addTo(map);
var layer = L.featureGroup();
$features
map.addLayer(layer);
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

$formats['js'] = new JSFormat();

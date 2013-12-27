<?php

class IFrameFormat implements Format {
    public $mime = 'text/html';

    public function export( $data ) {
        global $scodeid, $base_path;
        $version = '1.2.0';
        $endpoint = $base_path.'/';
        $codeid = isset($scodeid) ? $scodeid : '';
        $code = json_encode(array_to_mapbbcode($data));
        $title = isset($data['title']) && strlen(trim($data['title'])) > 0 ?
            '<div id="title">'.htmlspecialchars($data['title']).'</div>' : '';

        $html = <<<HTMLE
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<link rel="stylesheet" href="//cdn.jsdelivr.net/mapbbcode/$version/leaflet.css" />
<script src="//cdn.jsdelivr.net/mapbbcode/$version/leaflet.js"></script>
<script src="//cdn.jsdelivr.net/mapbbcode/$version/mapbbcode.js"></script>
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
}
</style>
</head>
<body>
<div id="map"></div>
$title
<script>
L.DomEvent.on(window, 'load', function() {
    var code = $code;
    var map = new window.MapBBCode({
        fullFromStart: true,
        fullViewHeight: 0
    });
    var c = map.show('map', code);

    c.map.addControl(L.functionButtons([{
        content: window.MapBBCode.buttonsImage,
        bgPos: [52, 0],
        href: '$endpoint$codeid',
        alt: '&#x21B7;',
        title: map.strings.outerTitle
    }], { position: 'topright' }));

    if( L.ExportControl && '$codeid' ) {
        c.map.addControl(new L.ExportControl({
            name: map.strings.exportName,
            title: map.strings.exportTitle,
            filter: map.options.exportTypes.split(','),
            endpoint: '$endpoint',
            codeid: '$codeid'
        }));
    }
});
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

$formats['iframe'] = new IFrameFormat();

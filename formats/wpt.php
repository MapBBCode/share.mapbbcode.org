<?php

class WPTFormat implements Format {
    public $title = 'WPT';
    public $mime = 'text/plain';

    public function export( $data ) {
        $out = <<<WPTHEAD
OziExplorer Waypoint File Version 1.1
WGS 84
Reserved 2
Reserved 3

WPTHEAD;
        $i = 0;
        setlocale(LC_ALL, 'en_GB');
        foreach( $data['objs'] as $obj ) {
            if( count($obj['coords']) == 1 ) {
                $i++;
                $c = $obj['coords'][0];
                $text = isset($obj['text']) && strlen(trim($obj['text'])) > 0 ? trim($obj['text']) : $i;
                if( defined('OZI_CHARSET') )
                    $text = iconv('UTF-8', OZI_CHARSET.'//TRANSLIT', $text);
                $text = str_replace(',', chr(209), trim($text));
                $out .= sprintf("%d,%s,%.5f,%.5f\n", $i, $text, $c[0], $c[1]);
            }
        }
        return $out;
    }

    public function test( $header ) {
        return preg_match('/^OziExplorer Waypoint File Version 1/', $header);
    }

    public function import( $file ) {
        $objs = array();
        $i = 0;
        while( ($line = fgets($file, 500)) !== false ) {
            $items = explode(',', trim($line));
            if( ++$i > 4 && count($items) > 3 ) {
                $title = $items[1];
                if( defined('OZI_CHARSET') )
                    $title = iconv(OZI_CHARSET, 'UTF-8//IGNORE', str_replace(chr(209), ',', $title));
                $lat = $items[2];
                $lon = $items[3];
                if( is_numeric($lat) && is_numeric($lon) ) {
                    $coord = array((float)$lat, (float)$lon);
                    $obj = array( 'coords' => array($coord) );
                    if( strlen(trim($title)) > 0 )
                        $obj['text'] = trim($title);
                    $objs[] = $obj;
                }
            }
        }
        return array('objs' => $objs);
    }
}

$formats['wpt'] = new WPTFormat();

?>

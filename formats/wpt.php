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
                // iconv('UTF-8', 'ASCII//TRANSLIT', $obj['text']) doesn't work properly
                $text = isset($obj['text']) ? str_replace(',', chr(209), trim($obj['text'])) : '';
                if( strlen($text) == 0 )
                    $text = $i;
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
            $items = explode(',', $line);
            if( ++$i > 4 && count($items) > 3 ) {
                $title = $items[1];
                $lat = $items[2];
                $lon = $items[3];
                if( is_numeric($lat) && is_numeric($lon) ) {
                    $coord = array((float)$lat, (float)$lon);
                    $obj = array( 'coords' => array($coord) );
                    if( strlen(trim($title)) > 0 )
                        $obj['text'] = str_replace(chr(209), ',', $title);
                    $objs[] = $obj;
                }
            }
        }
        return array('objs' => $objs);
    }
}

$formats['wpt'] = new WPTFormat();

?>

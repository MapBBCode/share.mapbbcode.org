<?php

class NMEAFormat implements Format {
    public $simplify = true;

    public function export( $data ) {
    }

    public function test( $header ) {
        return preg_match('/(^\$[A-Z]{5},.+?){3}/ms', $header);
    }

    public function import( $file ) {
        $coords = array();
        $cnt = 0;
        while( ($line = fgets($file, 300)) !== false ) {
            $items = explode(',', $line);
            if( count($items) > 10 && strlen($items[0]) == 6 && substr($items[0], 3) == 'GGA' ) {
                $lat = $this->parse_coord($items[2], $items[3]);
                $lon = $this->parse_coord($items[4], $items[5]);
                if( is_numeric($lat) && is_numeric($lon) ) {
                    $coord = array($lat, $lon);
                    if( $cnt == 0 || $coords[$cnt-1] != $coord ) {
                //echo "$line $lat $lon<br>";
                        $coords[] = $coord;
                        $cnt++;
                    }
                }
            }
        }
        return $cnt > 0 ? array('objs' => array(array('coords' => $coords))) : false;
    }

    private function parse_coord( $value, $hemisphere ) {
        if( !is_numeric($value) || strlen($hemisphere) != 1 )
            return false;
        $degrees = round((float)$value / 100);
        $minutes = (float)$value - $degrees * 100;
        $coord = round(($degrees + $minutes / 60) * 10000) / 10000;
        return $hemisphere == 'S' || $hemisphere == 'W' ? -$coord : $coord;
    }
}

$formats['nmea'] = new NMEAFormat();

?>

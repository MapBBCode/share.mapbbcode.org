<?php

class WKTFormat implements Format {
    public $title = 'WKT';
    public $mime = 'application/wkt';

    public function export( $data ) {
        $wkt = array();
        foreach( $data['objs'] as $obj ) {
            $coords = $obj['coords'];
            $coordstr = $this->format_coords($coords);
            $cnt = count($coords);
            if( $cnt == 1 ) {
                $wkt[] = "POINT($coordstr)";
            } elseif( $cnt > 1 ) {
                if( $coords[0][0] == $coords[$cnt-1][0] && $coords[0][1] == $coords[$cnt-1][1] ) {
                    $wkt[] = "POLYGON(($coordstr))";
                } else {
                    $wkt[] = "LINESTRING($coordstr)";
                }
            } else
                continue;
        }
        return count($wkt) == 0 ? '' : (count($wkt) == 1 ? $wkt[0] : 'GEOMETRYCOLLECTION('.implode(',', $wkt).')');
    }

    private function format_coords( $coords ) {
        $res = array();
        foreach( $coords as $c ) {
            $res[] = $c[1].' '.$c[0];
        }
        return implode(', ', $res);
    }

    public function test( $header ) {
        return preg_match('/^(GEOMETRYCOLLECTION|POINT|POLYGON|LINESTRING)\(/', $header);
    }

    public function import( $file ) {
        $content = fread($file, 50000);
        if( !preg_match_all('/(POINT|POLYGON|LINESTRING)[\s\(]*([^)]+)[\s\)]*/', $content, $m, PREG_SET_ORDER) )
            return false;
        $objs = array();
        foreach( $m as $str ) {
            $coordstr = preg_split('/[\s,]+/', $str[2], -1, PREG_SPLIT_NO_EMPTY);
            $coords = array();
            for( $i = 0; $i+1 < count($coordstr); $i += 2 ) {
                $coords[] = array((float)$coordstr[$i+1], (float)$coordstr[$i]);
            }
            // type doesn't really matter
            $objs[] = array('coords' => $coords);
        }
        return array('objs' => $objs);
    }
}

$formats['wkt'] = new WKTFormat();

?>

<?php

class PolyFormat implements Format {
    public $title = 'poly';
    public $priority = -9;

    public function export( $data ) {
        $title = isset($data['title']) && strlen(trim($data['title'])) > 0 ? trim($data['title']) : 'shared';
        $poly = "$title\n";
        $section = 1;
        $inner = ''; // 0 - outer, 1 - inner
        foreach( $data['objs'] as $obj ) {
            $coords = $obj['coords'];
            if( count($coords) > 2 && $coords[0] == $coords[count($coords)-1] ) {
                $ring = ($section++)."\n";
                foreach( $coords as $c )
                    $ring .= sprintf("   %E   %E\n", $c[1], $c[0]);
                $ring .= "END\n";
                if( isset($obj['params']) && in_array('green', $obj['params']) ) // yes, green. That's a plug
                    $inner .= $ring;
                else
                    $poly .= $ring;
            }
        }
        return $poly.$inner."END\n";
    }

    public function test( $header ) {
        return preg_match('/^[^\n\r]+?[\r\n]+!?\d+[\r\n]\s*-?\d/s', $header); // very loose, yes
    }

    public function import( $file ) {
        $title = fgets($file, 200);
        $objs = array();
        $section = fgets($file, 100);
        while( $section !== false && strlen($section) > 0 && $section !== 'END' ) {
            $coords = array();
            while( ($line = fgets($file, 100)) !== false ) {
                if( $line == 'END' )
                    break;
                if( preg_match('/^\s*(\S+)\s+(\S+)/', $line, $m) )
                    if( is_numeric($m[1]) && is_numeric($m[2]) )
                        $coords[] = array((float)$m[2], (float)$m[1]);
            }
            $params = substr($section, 0, 1) == '!' ? array('green') : array();
            if( count($coords) > 2 ) {
                $last = $coords[count($coords)-1];
                if( $coords[1] == $last )
                    array_shift($coords);
                elseif( $coords[0] != $last )
                    $coords[] = $coords[0];
                $objs[] = array('coords' => $coords, 'params' => $params);
            }
            $section = fgets($file, 100);
        }
        return count($objs) > 0 ? array('title' => $title, 'objs' => $objs) : false;
    }
}

$formats['poly'] = new PolyFormat();

?>

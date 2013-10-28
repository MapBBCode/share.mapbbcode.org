<?php // This is a port of MapBBCode.js

function get_mapbbcode_regexp() {
    $re = array();
    $re['coord'] = '\\s*(-?\\d+(?:\\.\\d+)?)\\s*,\\s*(-?\\d+(?:\\.\\d+)?)';
    $re['params'] = '\\((?:([a-zA-Z0-9,]*)\\|)?(|[\\s\\S]*?[^\\\\])\\)';
    $re['mapel'] = $re['coord'].'(?:'.$re['coord'].')*(?:\\s*'.$re['params'].')?';
    $re['maptag'] = '\\[map(?:=([12]?\\d)(?:,'.$re['coord'].')?)?\\]';
    $re['map'] = $re['maptag'].'('.$re['mapel'].'(?:\\s*;'.$re['mapel'].')*)?\\s*\\[\\/map\\]';
//    $re['map'] = $re['maptag'].'\\[/map\\]';
    return $re;
}

function is_mapbbcode_valid( $bbcode ) {
    $re = get_mapbbcode_regexp();
    return preg_match('/'.$re['map'].'/', bbcode);
}

function mapbbcode_stats( $bbcode ) {
    $re = get_mapbbcode_regexp();
    $cnt = array(0, 0, 0);
    if( !preg_match('/'.$re['map'].'/', $bbcode, $m ) )
        return $cnt;

    if( count($m) > 4 ) {
        $items = explode(';', str_replace(';;', '##%##', $m[4]));
        foreach( $items as $s ) {
            $s = str_replace('##%##',';', $s);
            $coords = 0;
            $first;
            $eqfirst = false;
            $hnc = preg_match('/^'.$re['coord'].'/', $s, $mc);
            while( $hnc ) {
                $coords++;
                $c = array((float)$mc[1], (float)$mc[2]);
                if( !isset($first) )
                    $first = $c;
                else
                    $eqfirst = $c[0] == $first[0] && $c[1] == $first[1];
                $s = substr($s, strlen($mc[0]));
                $hnc = preg_match('/^'.$re['coord'].'/', $s, $mc);
            }
            if( $coords == 1 )
                $cnt[0]++;
            elseif( $coords > 1 && !$eqfirst )
                $cnt[1]++;
            elseif( $coords > 1 && $eqfirst )
                $cnt[2]++;
        }
    }

    return $cnt;
}

function mapbbcode_to_array( $bbcode ) {
    $re = get_mapbbcode_regexp();
    $result = array('objs' => '');
    if( !preg_match('/'.$re['map'].'/', $bbcode, $m ) )
        return $result;

    if( count($m) > 1 && $m[1] > 0 ) {
        $result['zoom'] = (int)$m[1];
        if( count($m) > 3 && strlen($m[2]) > 0 && strlen($m[3]) > 0 ) {
            $result['pos'] = array((float)$m[2], (float)$m[3]);
        }
    }

    if( count($m) > 4 ) {
        // todo: parse elements etc etc
        $items = explode(';', str_replace(';;', '##%##', $m[4]));
        foreach( $items as $s ) {
            $s = str_replace('##%##',';', $s);
            $coords = array();
            $params = array();
            $text = '';
            $hnc = preg_match('/^'.$re['coord'].'/', $s, $mc);
            while( $hnc ) {
                $coords[] = array((float)$mc[1], (float)$mc[2]);
                $s = substr($s, strlen($mc[0]));
                $hnc = preg_match('/^'.$re['coord'].'/', $s, $mc);
            }
            if( preg_match('/'.$re['params'].'/', $s, $mc ) ) {
                if( strlen($mc[1]) > 0 )
                    $params = explode(',', $mc[1]);
                $text = trim(str_replace('\\)', ')', $mc[2]));
            }
            $result['objs'][] = array('coords' => $coords, 'text' => $text, 'params' => $params);
        }
    }

    return $result;
}

function array_to_mapbbcode( $data ) {
    $mapdata = '';
    if( isset($data['zoom']) && $data['zoom'] > 0 ) {
        $mapdata = '='.$data['zoom'];
        if( isset($data['pos']) && count($data['pos']) == 2 ) {
            $mapdata .= ','.latlng_to_string($data['pos']);
        }
    }

    $markers = array();
    $paths = array();
    $objs = isset($data['objs']) && is_array($data['objs']) ? $data['objs'] : array();
    foreach( $objs as $obj ) {
        $coords = $obj['coords'];
        for( $i = 0; $i < count($coords); $i++ )
            $coords[$i] = latlng_to_string($coords[$i]);
        $str = implode(' ', $coords);

        $text = isset($obj['text']) ? $obj['text'] : '';
        $params = isset($obj['params']) && is_array($obj['params']) ? $obj['params'] : array();
        if( strpos($text, '|') !== false && count($params) == 0 )
            $text = '|'.$text;
        if( strlen($text) > 0 || count($params) > 0 ) {
            $str .= '(';
            if( count($params) > 0 )
                $str .= implode(',', $params).'|';
            $str .= str_replace(';', ';;', str_replace(')', '\\)', $text)).')';
        }
        if( count($coords) == 1 )
            $markers[] = $str;
        elseif( count($coords) > 1 )
            $paths[] = $str;
    }
    
    return count($markers) > 0 || count($paths) > 0 || strlen($mapdata) > 0 ? '[map'.$mapdata.']'.implode(';', array_merge($markers, $paths)).'[/map]' : '';
}

function latlng_to_string( $latlng ) {
    return number_format($latlng[0], 5, '.', '').','.number_format($latlng[1], 5, '.', '');
}

?>
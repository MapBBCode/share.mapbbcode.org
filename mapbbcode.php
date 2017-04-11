<?php // This is a port of MapBBCode.js

function get_mapbbcode_regexp() {
    $re = array();
    $re['coord'] = '\\s*(-?\\d+(?:\\.\\d+)?)\\s*,\\s*(-?\\d+(?:\\.\\d+)?)';
    $re['params'] = '\\((?:([a-zA-Z0-9,]*)\\|)?(|[\\s\\S]*?[^\\\\])\\)';
    $re['mapel'] = $re['coord'].'(?:'.$re['coord'].')*(?:\\s*'.$re['params'].')?';
    $re['maptag'] = '\\[map(?:=([12]?\\d)(?:,'.$re['coord'].')?)?\\]';
    $re['map'] = $re['maptag'].'('.$re['mapel'].'(?:\\s*;'.$re['mapel'].')*)?\\s*\\[\\/map\\]';
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
        $items = $m[4];
        while( preg_match('/^\\s*(?:;\\s*)?('.$re['mapel'].')/', $items, $itm) ) {
            $s = $itm[1];
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
            $items = substr($items, strlen($itm[0]));
        }
    }

    return $cnt;
}

function mapbbcode_to_array( $bbcode ) {
    $re = get_mapbbcode_regexp();
    $result = array();
    if( !preg_match('/'.$re['map'].'/', $bbcode, $m ) )
        return $result;

    if( count($m) > 1 && $m[1] > 0 ) {
        $result['zoom'] = (int)$m[1];
        if( count($m) > 3 && strlen($m[2]) > 0 && strlen($m[3]) > 0 ) {
            $result['pos'] = array((float)$m[2], (float)$m[3]);
        }
    }

    if( count($m) > 4 ) {
        $items = $m[4];
        while( preg_match('/^\\s*(?:;\\s*)?('.$re['mapel'].')/', $items, $itm) ) {
            $s = $itm[1];
            $coords = array();
            $params = array();
            $text = '';
            $hnc = preg_match('/^'.$re['coord'].'/', $s, $mc);
            while( $hnc ) {
                $coords[] = array((float)$mc[1], (float)$mc[2]);
                $s = substr($s, strlen($mc[0]));
                $hnc = preg_match('/^'.$re['coord'].'/', $s, $mc);
            }
            if( count($itm) > 6 && strlen($itm[6]) > 0 )
                $params = explode(',', $itm[6]);
            if( count($itm) > 7 && strlen(trim($itm[7])) > 0 )
                $text = trim(str_replace('\\)', ')', $itm[7]));
            $result['objs'][] = array('coords' => $coords, 'text' => $text, 'params' => $params);
            $items = substr($items, strlen($itm[0]));
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
            $str .= str_replace(')', '\\)', $text).')';
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

function merge_mapbbcode( $bbcode1, $bbcode2 ) {
    $re = get_mapbbcode_regexp();
    if( !preg_match('/'.$re['map'].'/', $bbcode1, $m1) )
        return $bbcode2;
    if( !preg_match('/'.$re['map'].'/', $bbcode2, $m2) )
        return $bbcode1;
    $bb1 = count($m1) > 4 ? $m1[4] : '';
    $bb2 = count($m2) > 4 ? ';'.$m2[4] : '';
    return '[map]'.$bb1.$bb2.'[/map]';
}

?>

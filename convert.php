<?php
require_once('mapbbcode.php');

const CONVERT_OK = 0;
const CONVERT_NOT_SUPPORTED = 1;
const CONVERT_EMPTY = 2;

interface Format {
    // -- all properties are optional
    // string $mime - mime type of the format
    // string $title - displayed title of the format
    // string $ext - file extension
    // int $priority - determines when format is queried for import (lower = later)
    // boolean $simplify - if true, paths are simplified after importing
    // boolean $import_filename - import() requires filename instead of file handle (e.g. because of XMLReader)

    // returns string representation of data array (see mapbbcode.php)
    public /* string */ function export( $data );

    // checks if data (represented by its header) can be imported as this format
    public /* boolean */ function test( $header );

    // parses file ($file is a handler) and returns objects array (see mapbbcode.php)
    public /* object */ function import( $file );

    // last line of every format script should include
    // $formats['type'] = new ClassName();
}

$formats = array();
foreach( glob('formats/*.php') as $file ) {
    include($file);
}

// find an object for given format
function get_format( $type ) {
    return isset($formats[$type]) ? $formats[$type] : false;
}

// return array(types, titles) of all supported formats
function get_format_arrays() {
    global $formats;
    $etypes = array();
    $etitles = array();
    foreach( $formats as $type => $fmt ) {
        if( isset($fmt->title) ) {
            $etypes[] = $type;
            $etitles[] = $fmt->title;
        }
    }
    return array('types' => $etypes, 'titles' => $etitles);
}

// parses bbcode and prints exported file. Returns CONVERT_* status
function export( $type, $title, $bbcode, $codeid = '', $attach = true, $break_on_empty = true ) {
    global $formats;
    if( isset($formats[$type]) ) {
        $fmt = $formats[$type];
        require_once('mapbbcode.php');
        $data = mapbbcode_to_array($bbcode);
        $data['title'] = $title;
		if( !is_array($data['objs']) )
			$data['objs'] = array();
        if( count($data['objs']) > 0 || isset($data['zoom']) ) {
            $content = $fmt->export($data);
            if( !$break_on_empty || strlen($content) > 0 ) {
                $basename = trim(preg_replace('/[^ 0-9a-z_.,!()-]+/i', '', iconv('UTF-8', 'ASCII//TRANSLIT', $title)));
                if( $basename == '' )
                    $basename = preg_match('/^\w+$/', $codeid) ? $codeid : 'shared';
                header("Cache-Control: no-cache, must-revalidate");
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
                header('Content-Type: '.(isset($fmt->mime) ? $fmt->mime : 'text/plain'));
                if( $attach )
                    header('Content-Disposition: attachment; filename='.$basename.'.'.(isset($fmt->ext) ? $fmt->ext : $type));
                header('Content-Length: '.mb_strlen($content, '8bit'));
                print $content;
                return CONVERT_OK;
            }
        }
        return CONVERT_EMPTY;
    }
    return CONVERT_NOT_SUPPORTED;
}

// format comparator, by priority
function compare_formats( $fmta, $fmtb ) {
    $pa = isset($fmta->priority) ? $fmta->priority : 0;
    $pb = isset($fmtb->priority) ? $fmtb->priority : 0;
    return $pa < $pb ? 1 : ($pa == $pb ? 0 : -1);
}

// determines format for given file header
function import_get_format( $header ) {
    global $formats;
    $fmts = array();
    foreach( $formats as $type => $fmt ) {
        $fmts[] = $fmt;
    }
    usort($fmts, 'compare_formats');
    foreach( $fmts as $fmt ) {
        if( $fmt->test($header) )
            return $fmt;
    }
    return false;
}

// reads file and returns array(title, bbcode) for it. On error returns array('', '')
function import( $filename, $old_titlebb ) {
    $data = false;
    if( ($handle = fopen($filename, 'r')) !== false ) {
        $header = fread($handle, 2000);
        $fmt = import_get_format($header);
        if( $fmt !== false ) {
            if( isset($fmt->import_filename) && $fmt->import_filename ) {
                fclose($handle);
                $data = $fmt->import($filename);
            } else {
                if( !rewind($handle) ) {
                    fclose($handle);
                    $handle = fopen($filename, 'r');
                }
                $data = $fmt->import($handle);
                fclose($handle);
            }
            if( $data && isset($data['objs']) && count($data['objs']) > 0 && isset($fmt->simplify) && $fmt->simplify )
                $data['objs'] = reduce_points($data['objs']);
        } else
            fclose($handle);
    }

    if( $data ) {
        $title = strlen($old_titlebb[0]) > 0 ? $old_titlebb[0] : (isset($data['title']) ? $data['title'] : '');
        $bbcode = merge_mapbbcode(array_to_mapbbcode($data), $old_titlebb[1]);
        return array($title, $bbcode);
    }
    return $old_titlebb;
}

// simplifies paths in the array
function reduce_points( $objs ) {
    global $message;
    $nodes = 0;
    $count = 0;
    foreach( $objs as $obj ) {
        if( count($obj['coords']) > 1 ) {
            $nodes += count($obj['coords']);
            $count++;
        }
    }
    $max = $nodes > MAX_TOTAL_NODES ? min(floor(MAX_TOTAL_NODES / $count), MAX_IMPORT_NODES) : MAX_IMPORT_NODES;
    $nodes2 = $nodes;

    foreach( $objs as &$obj ) {
        if( count($obj['coords']) > $max) {
            $orig = count($obj['coords']);
            $obj['coords'] = iterate_simplify($obj['coords'], $max);
            $nodes2 -= $orig - count($obj['coords']);
        }
    }
    unset($obj);

    if( $nodes2 < $nodes )
        $message = "Traces were simplified: $nodes points down to $nodes2";

    return $objs;
}

require_once('simplify.php');

// iterative simplify, so number of points is between 90% and 100% of $max
function iterate_simplify( $points, $max ) {
    if( count($points) <= $max )
        return $points;
    //echo 'Reducing points (max='.$max.') from '.count($points);
    $min = round($max * 0.9);
    $tolerance = 0.00001;
    $tol1 = 0;
    $tol2 = 0;
    $steps = 0;
    while( $steps++ < 10 ) {
        $p = simplify($points, $tolerance);
        $cnt = count($p);
        if( $cnt > $max ) {
            $tol1 = $tolerance;
            $tolerance = $tol2 < $tolerance ? $tolerance * 2 : ($tolerance + $tol2) / 2;
        } elseif( $cnt < $min ) {
            $tol2 = $tolerance;
            $tolerance = ($tol1 + $tolerance) / 2;
        } else
            break;
    }
    //echo ' to '.count($p).' ('.$steps.' iterations)<br>';
    return $p;
}

?>

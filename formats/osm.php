<?php

class OSMFormat implements Format {
    public $title = 'OSM';
    public $mime = 'application/x-openstreetmap+xml';
    public $import_filename = true;

    private $colors = array('blue', 'red', 'green', 'brown', 'purple', 'black');

    public function export( $data ) {
        $nodes = array();
        $nextnodeid = -1;
        $nextwayid = -1;
        $nodestr = '';
        $waystr = '';
        foreach($data['objs'] as $obj ) {
            $cnt = count($obj['coords']);
            $tags = array();
            if( isset($obj['text']) && strlen(trim($obj['text'])) > 0 )
                $tags['name'] = trim($obj['text']);
            if( $cnt > 1 && isset($obj['params']) ) {
                foreach( $obj['params'] as $param ) {
                    if( in_array($param, $this->colors ) ) {
                        $tags['color'] = $param;
                        break;
                    }
                }
            }
            if( $cnt == 1 && count($tags) == 0 ) {
                // mark node as a marker
                $tags['marker'] = 'yes';
            }
            $tagstr = '';
            foreach( $tags as $tag => $value )
                $tagstr .= "    <tag k='$tag' v='".htmlspecialchars($value)."' />\n";

            $nodeids = array();
            foreach( $obj['coords'] as $coord ) {
                // avoid duplicate nodes (that will also close polygons)
                $nodeid = false;
                foreach( $nodes as $id => $ll ) {
                    if( $ll == $coord ) { // we assume php compares arrays
                        $nodeid = $id;
                        break;
                    }
                }
                if( !$nodeid ) {
                    $nodeid = $nextnodeid--;
                    $nodes[$nodeid] = $coord;
                    $nodestr .= "  <node id='$nodeid' visible='true' lat='${coord[0]}' lon='${coord[1]}'";
                    $nodestr .= $cnt == 1 && strlen($tagstr) > 0 ? ">\n$tagstr  </node>\n" : " />\n";
                }
                $nodeids[] = $nodeid;
            }

            if( $cnt > 1 ) {
                $way = "  <way id='".($nextwayid--)."' visible='true'>\n";
                foreach( $nodeids as $nodeid )
                    $way .= "    <nd ref='$nodeid' />\n";
                $way .= $tagstr."  </way>\n";
                $waystr .= $way;
            }
        }
        $osm = <<<OSM
<?xml version='1.0' encoding='UTF-8'?>
<osm version='0.6' upload='false' generator='MapBBCode Share'>
$nodestr
$waystr
</osm>
OSM;
        return $osm;
    }

    public function test( $header ) {
        return preg_match('/<\?xml.+<osm.+[\'"]0\.6[\'"]/s', $header);
    }

    public function import( $file ) {
        $objs = array();
        $xml = new XMLReader();
        $xml->open($file, 'UTF-8', LIBXML_NONET);
        if( !$xml )
            return false;
        $mode = '';
        $nodes = array(); // we assume nodes come before ways
        $straynodes = array();
        while( @$xml->read() ) {
            if( $xml->nodeType == XMLReader::ELEMENT ) {
                if( $xml->name == 'node' ) {
                    $mode = 'node';
                    $id = $xml->getAttribute('id');
                    $lat = $xml->getAttribute('lat');
                    $lon = $xml->getAttribute('lon');
                    if( is_numeric($id) && is_numeric($lat) && is_numeric($lon) ) {
                        $nodes[$id] = array($lat, $lon);
                        $straynodes[$id] = 1;
                    }
                } elseif( $xml->name == 'way' ) {
                    $mode = 'way';
                    $id = $xml->getAttribute('id');
                    $waynodes = array();
                } elseif( $xml->name == 'tag' ) {
                    $k = $xml->getAttribute('k');
                    $v = $xml->getAttribute('v');
                    if( $k != null && $v != null ) {
                        if( !isset($tags) )
                            $tags = array();
                        $tags[$k] = $v;
                    }
                } elseif( $xml->name == 'nd' ) {
                    $ref = $xml->getAttribute('ref');
                    if( is_numeric($ref) && isset($nodes[$ref]) ) {
                        $waynodes[] = $nodes[$ref];
                        if( isset($straynodes[$ref]) )
                            unset($straynodes[$ref]);
                    }
                }
            } elseif( $xml->nodeType == XMLReader::END_ELEMENT ) {
                if( strlen($mode) > 0 && $xml->name == $mode ) {
                    if( $mode == 'node' && count($tags) > 0 ) {
                        $waynodes = array($nodes[$id]);
                        if( isset($straynodes[$id]) )
                            unset($straynodes[$id]);
                    }
                    if( isset($waynodes) ) {
                        $obj = array('coords' => $waynodes);
                        if( isset($tags) ) {
                            if( isset($tags['name']) )
                                $obj['text'] = $tags['name'];
                            if( isset($tags['color']) && $mode != 'node' ) {
                                if( in_array($tags['color'], $this->colors) )
                                    $obj['params'] = array($tags['color']);
                            }
                        }
                        $objs[] = $obj;
                        unset($waynodes);
                    }
                    if( isset($tags) )
                        unset($tags);
                    if( isset($id) )
                        unset($id);
                    $mode = '';
                }
            }
        }
        $xml->close();

        // make stray nodes into markers
        foreach( $straynodes as $id => $whatever )
            $objs[] = array('coords' => array($nodes[$id]));

        return array('objs' => $objs );
    }
}

$formats['osm'] = new OSMFormat();

?>

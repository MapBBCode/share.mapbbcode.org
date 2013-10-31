<?php

class GPXFormat implements Format {
    public $title = 'GPX';
    public $mime = 'application/gpx+xml';
    public $simplify = true;
    public $import_filename = true;

    public function export( $data ) {
        $out = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<gpx xmlns=\"http://www.topografix.com/GPX/1/1\" creator=\"share.mapbbcode.org\" version=\"1.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd\">\n";
        if( strlen($data['title']) > 0 ) {
            $out .= " <metadata>\n  <name>".htmlspecialchars($data['title'])."</name>\n </metadata>\n";
        }
        $wpt = '';
        $trk = '';
        foreach( $data['objs'] as $obj ) {
            if( count($obj['coords']) == 1 ) {
                $wpt .= sprintf(" <wpt lat=\"%.5f\" lon=\"%.5f\">\n", $obj['coords'][0][0], $obj['coords'][0][1]);
                if( isset($obj['text']) && strlen($obj['text']) > 0 )
                    $wpt .= '  <name>'.htmlspecialchars($obj['text'])."</name>\n";
                $wpt .= " </wpt>\n";
            } elseif( count($obj['coords']) > 1 ) {
                $trk .= " <trk>\n";
                if( isset($obj['text']) && strlen($obj['text']) > 0 )
                    $trk .= '  <name>'.htmlspecialchars($obj['text'])."</name>\n";
                $trk .= "  <trkseg>\n";
                foreach( $obj['coords'] as $c ) {
                    $trk .= sprintf("   <trkpt lat=\"%.5f\" lon=\"%.5f\" />\n", $c[0], $c[1]);
                }
                $trk .= "  </trkseg>\n </trk>\n";
            }
        }
        $out .= $wpt.$trk."</gpx>";
        return $out;
    }

    public function test( $header ) {
        return strpos($header, '<gpx') !== false && strpos($header, '/GPX/1') !== false;
    }

    public function import( $file ) {
        $objs = array();
        $title = '';
        $xml = new XMLReader();
        $xml->open($file, 'UTF-8', LIBXML_NONET);
        if( !$xml )
            return false;
        $mode = '';
        $inobject = false;
        while( @$xml->read() ) {
            if( $xml->nodeType == XMLReader::ELEMENT ) {
                if( $xml->name == 'metadata' && $mode == '' ) {
                    $mode = 'metadata';
                } elseif( $xml->name == 'name' && $mode == 'metadata' ) {
                    $title = $xml->readString();
                } elseif( $xml->name == 'wpt' ) {
                    $mode = $xml->name;
                    $inobject = true;
                    $lat = $xml->getAttribute('lat');
                    $lon = $xml->getAttribute('lon');
                    if( $lat !== null && $lon !== null )
                        $coords = array(array($lat, $lon));
                } elseif( $xml->name == 'rte' || $xml->name == 'trkseg' ) {
                    $mode = $xml->name;
                    $inobject = true;
                    $coords = array();
                } elseif( ($xml->name == 'rtept' || $xml->name == 'trkpt') && $inobject ) {
                    $lat = $xml->getAttribute('lat');
                    $lon = $xml->getAttribute('lon');
                    if( $lat !== null && $lon !== null )
                        $coords[] = array($lat, $lon);
                } elseif( $xml->name == 'name' && $inobject ) {
                    $objtext = $xml->readString();
                }
            } elseif( $xml->nodeType == XMLReader::END_ELEMENT ) {
                if( $xml->name == $mode ) {
                    if( $mode == 'wpt' || $mode == 'trkseg' || $mode == 'rte' ) {
                        if( isset($coords) && count($coords) > 0 ) {
                            $obj = array('coords' => $coords);
                            if( isset($objtext) )
                                $obj['text'] = $objtext;
                            $objs[] = $obj;
                        }
                        if( isset($coords) )
                            unset($coords);
                        if( isset($objtext) )
                            unset($objtext);
                        $inobject = false;
                    }
                    $mode = '';
                }
            }
        }
        $xml->close();

        // finalize a trace if gpx file suddenly ended
        if( ($mode == 'wpt' || $mode == 'trkseg' || $mode == 'rte') && isset($coords) && count($coords) > 0 ) {
            $obj = array('coords' => $coords);
            if( isset($objtext) )
                $obj['text'] = $objtext;
            $objs[] = $obj;
        }

        $res = array('objs' => $objs );
        if( isset($title) && strlen($title) > 0 )
            $res['title'] = $title;
        return $res;
    }
}

$formats['gpx'] = new GPXFormat();

?>

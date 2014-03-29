<?php

class GPXFormat implements Format {
    public $title = 'GPX';
    public $mime = 'application/gpx+xml';
    public $simplify = true;
    public $import_filename = true;

	private function color_to_garmin( $params ) {
		foreach( $params as $c ) {
			if( $c == 'blue' ) return 'Blue';
			elseif( $c == 'red' ) return 'Red';
			elseif( $c == 'green' ) return 'Green';
			elseif( $c == 'brown' ) return 'Yellow';
			elseif( $c == 'purple' ) return 'Magenta';
			elseif( $c == 'black' ) return 'Black';
		}
		return 'Blue';
	}

	private function garmin_to_color( $c ) {
		if( strpos($c, 'Blue') !== false ) return false;
		if( strpos($c, 'Red') !== false ) return 'red';
		if( strpos($c, 'Green') !== false ) return 'green';
		if( strpos($c, 'Yellow') !== false ) return 'brown';
		if( strpos($c, 'Magenta') !== false ) return 'purple';
		if( strpos($c, 'Black') !== false ) return 'black';
		return false;
	}

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
				$colstr = isset($obj['params']) && count($obj['params']) > 0 ? $this->color_to_garmin($obj['params']) : false;
				if( $colstr )
					$trk .= "  <extensions>\n    <gpxx:TrackExtension xmlns:gpxx=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3\">\n      <gpxx:DisplayColor>$colstr</gpxx:DisplayColor>\n    </gpxx:TrackExtension>\n  </extensions>\n";
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
                } elseif( $xml->name == 'rte' || $xml->name == 'trkseg' || $xml->name == 'trk' ) {
                    $mode = $xml->name;
                    $inobject = true;
                    $coords = array();
                } elseif( ($xml->name == 'rtept' || $xml->name == 'trkpt') && $inobject ) {
                    $lat = $xml->getAttribute('lat');
                    $lon = $xml->getAttribute('lon');
                    if( $lat !== null && $lon !== null )
                        $coords[] = array($lat, $lon);
                } elseif( $xml->name == 'gpxx:DisplayColor' && $inobject ) {
					$objcolor = $this->garmin_to_color($xml->readString());
					if( !$objcolor )
						unset($objcolor);
                } elseif( $xml->name == 'name' && $inobject ) {
                    $objtext = $xml->readString();
                }
            } elseif( $xml->nodeType == XMLReader::END_ELEMENT ) {
                if( $xml->name == $mode ) {
                    if( $mode == 'wpt' || $mode == 'trkseg' || $mode == 'rte' || $mode == 'trk' ) {
						if( isset($coords) && count($coords) > 0 ) {
							$cnt = count($coords);
							if( count($coords) > 2 && $coords[0][0] == $coords[$cnt-1][0] && $coords[0][1] == $coords[$cnt-1][1] )
								array_pop($coords);
                            $obj = array('coords' => $coords);
                            if( isset($objtext) )
                                $obj['text'] = $objtext;
							if( isset($objcolor) )
								$obj['params'] = array($objcolor);
                            $objs[] = $obj;
                        }
                        if( isset($coords) )
                            unset($coords);
                        if( isset($objtext) )
                            unset($objtext);
                        if( isset($objcolor) )
                            unset($objcolor);
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

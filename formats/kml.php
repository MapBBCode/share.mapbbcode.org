<?php

class KMLFormat implements Format {
    public $title = 'KML';
    public $mime = 'application/vnd.google-earth.kml+xml';
    public $simplify = true;
    public $import_filename = true;

    private $colors = array(
        'blue' => '#0022dd',
        'red' => '#bb0000',
        'green' => '#007700',
        'brown' => '#964b00',
        'purple' => '#800080',
        'black' => '#000000'
    );
    
    public function export( $data ) {
        $title = isset($data['title']) && strlen($data['title']) > 0 ? '    <name>'.htmlspecialchars($data['title'])."</name>" : '';
        $styles = '';
        foreach( $this->colors as $name => $color ) {
            $kmlcolor = sprintf('%02s%02s%02s', substr($color, 5, 2), substr($color, 3, 2), substr($color, 1, 2));
            $styles .= <<<STYLE
    <Style id="${name}Line">
      <LineStyle>
        <color>b3$kmlcolor</color>
        <width>5</width>
      </LineStyle>
    </Style>
    <Style id="${name}Poly">
      <LineStyle>
        <color>b3$kmlcolor</color>
        <width>3</width>
      </LineStyle>
      <PolyStyle>
        <color>19$kmlcolor</color>
      </PolyStyle>
    </Style>

STYLE;
        }
        $placemarks = '';
        foreach( $data['objs'] as $obj ) {
            $text = isset($obj['text']) && strlen($obj['text']) > 0 ? '<name>'.htmlspecialchars($obj['text'])."</name>" : '';

            $geom = '';
            $coords = $obj['coords'];
            $cnt = count($coords);
            if( $cnt == 1 ) {
                $geom = 'Point';
            } elseif( $cnt > 1 ) {
                if( $coords[0][0] == $coords[$cnt-1][0] && $coords[0][1] == $coords[$cnt-1][1] ) {
                    $geom = 'Polygon';
                } else {
                    $geom = 'LineString';
                }
            }
            $coordstr = array();
            foreach( $coords as $c )
                $coordstr[] = $c[1].','.$c[0];
            $coordinates = '<coordinates>'.implode(' ', $coordstr).'</coordinates>';
            $polygon = <<<POLYGON
<outerBoundaryIs>
          <LinearRing>
            $coordinates
          </LinearRing>
        </outerBoundaryIs>
POLYGON;
            $geometry = $geom == 'Polygon' ? $polygon : $coordinates;

            $styleSuffix = $geom == 'Polygon' ? 'Poly' : ($geom == 'LineString' ? 'Line' : '');
            $styleUrl = '';
            if( isset($obj['params']) ) {
                foreach( $obj['params'] as $param ) {
                    if( isset($this->colors[$param]) ) {
                        $styleUrl = "<styleUrl>#$param$styleSuffix</styleUrl>";
                        break;
                    }
                }
            }
            $placemarks .= <<<PLACEM
    <Placemark>
      $text
      $styleUrl
      <$geom>
        $geometry
      </$geom>
    </Placemark>

PLACEM;
        }
        $kml = <<<KMLHEAD
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
 <Document>
$title
$styles
$placemarks
 </Document>
</kml>
KMLHEAD;
        return $kml;
    }

    public function test( $header ) {
        return preg_match('/<\?xml.+<kml.+kml\/2/s', $header);
    }

    public function import( $file ) {
        $objs = array();
        $xml = new XMLReader();
        $xml->open($file, 'UTF-8', LIBXML_NONET);
        if( !$xml )
            return false;
        $inmark = false;
        $innerRing = false;
        while( @$xml->read() ) {
            if( $xml->nodeType == XMLReader::ELEMENT ) {
                if( $xml->name == 'Placemark' ) {
                    $inmark = true;
                } elseif( $xml->name == 'name' ) {
                    if( $inmark )
                        $objtext = $xml->readString();
                    else
                        $title = $xml->readString();
                } elseif( $xml->name == 'styleUrl' ) {
                    $styleUrl = $xml->readString();
                    foreach( $this->colors as $k => $v ) {
                        if( strpos($styleUrl, $k) !== false ) {
                            $objcolor = $k;
                            break;
                        }
                    }
                } elseif( $xml->name == 'innerBoundaryIs' ) {
                    $innerRing = true;
                } elseif( $xml->name == 'coordinates' && !$innerRing ) {
                    $coordstr = preg_split('/\s+/', trim($xml->readString()));
                    $coords = array();
                    foreach( $coordstr as $latlon ) {
                        $ll = explode(',', $latlon);
                        if( count($ll) >= 2 ) {
                            $coords[] = array((float)$ll[1], (float)$ll[0]);
                        }
                    }
                }
            } elseif( $xml->nodeType == XMLReader::END_ELEMENT ) {
                if( $xml->name == 'Placemark' ) {
                    if( isset($coords) && count($coords) > 0 ) {
                        $obj = array('coords' => $coords);
                        if( isset($objcolor) ) {
                            $obj['params'] = array($objcolor);
                            unset($objcolor);
                        }
                        if( isset($objtext) ) {
                            $obj['text'] = $objtext;
                            unset($objtext);
                        }
                        $objs[] = $obj;
                        unset($coords);
                    }
                    $inplace = false;
                } elseif( $xml->name == 'innerBoundaryIs' ) {
                    $innerRing = false;
                }
            }
        }
        $xml->close();

        $res = array('objs' => $objs );
        if( isset($title) && strlen($title) > 0 )
            $res['title'] = $title;
        return $res;
    }
}

$formats['kml'] = new KMLFormat();

?>

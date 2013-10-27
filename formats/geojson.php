<?php

class GeoJSONFormat implements Format {
    public $title = 'GeoJSON';
    public $mime = 'application/json';
    
    private $colors = array(
        'blue' => '#0022dd',
        'red' => '#bb0000',
        'green' => '#007700',
        'brown' => '#964b00',
        'purple' => '#800080',
        'black' => '#000000'
    );
    
    public function export( $data ) {
        $out = array( 'type' => 'FeatureCollection' );
        if( strlen($data['title']) > 0 ) {
            $out['title'] = $data['title'];
        }
        $features = array();
        foreach( $data['objs'] as $obj ) {
            $cnt = count($obj['coords']);
            $coords = $this->swap_latlon($obj['coords']);
            if( $cnt == 1 ) {
                $geom = 'Point';
                $coords = $coords[0];
            } elseif( $cnt > 1 ) {
                if( $coords[0][0] == $coords[$cnt-1][0] && $coords[0][1] == $coords[$cnt-1][1] ) {
                    $geom = 'Polygon';
                    $coords = array($coords);
                } else {
                    $geom = 'LineString';
                }
            } else
                continue;
            $props = array();
            if( isset($obj['text']) && strlen($obj['text']) > 0 )
                $props['title'] = $obj['text'];
            if( isset($obj['params']) ) {
                foreach( $obj['params'] as $param ) {
                    if( isset($this->colors[$param]) ) {
                        $props['color'] = $this->colors[$param];
                        break;
                    }
                }
            }
            $features[] = array( 'type' => 'Feature', 'properties' => (object)$props, 'geometry' => array( 'type' => $geom, 'coordinates' => $coords ) );
        }
        $out['features'] = $features;
        return json_encode($out);
    }

    private function swap_latlon( $coords ) {
        $result = array();
        foreach( $coords as $c )
            $result[] = array($c[1], $c[0]);
        return $result;
    }

    public function test( $header ) {
        return preg_match('/"type"\s*:\s*"Feature"/', $header);
    }

    public function import( $file ) {
        $content = fread($file, 50000);
        $json = json_decode($content, true);
        if( !$json )
            return false;
        $data = $this->parse_object($json);
        $title = $json && isset($json['title']) ? $json['title'] : '';
        return $data ? array('objs' => $data, 'title' => $title) : false;
    }

    // parses single object and returns array of objects for mapbbcode
    private function parse_object( $obj ) {
        if( !$obj || !isset($obj['type']) )
            return false;
        $type = $obj['type'];
        $objs = array();
        if( $type == 'Feature' && isset($obj['geometry']) ) {
            $props = isset($obj['properties']) ? $obj['properties'] : array();
            // colour
            $params = array();
            if( isset($props['color']) ) {
                $color = $props['color'];
                foreach( $this->colors as $c => $v ) {
                    if( $color == $v ) {
                        $params[] = $c;
                        break;
                    }
                }
            }
            // title
            $title = isset($props['title']) ? $props['title'] : '';
            // create objects for every included geometry
            $coords = $this->parse_geometry($obj['geometry']);
            foreach( $coords as $coord ) {
                $feat = array('coords' => $coord, 'params' => $params);
                if( strlen($title) > 0 )
                    $feat['text'] = $title;
                $objs[] = $feat;
            }
        } elseif( $type == 'FeatureCollection' && isset($obj['features']) ) {
            foreach( $obj['features'] as $feat ) {
                $f = $this->parse_object($feat);
                if( $f )
                    $objs = array_merge($objs, $f);
            }
        }
        return count($objs) > 0 ? $objs : false;
    }

    // parses single object and returns array of objects for mapbbcode
    private function parse_geometry( $obj ) {
        if( !$obj || !isset($obj['type']) )
            return false;

        $type = $obj['type'];
        if( $type == 'GeometryCollection' && isset($obj['geometries']) ) {
            $coll = array();
            foreach( $obj['geometries'] as $geom ) {
                $p = $this->parse_geometry($geom);
                if( $p )
                    $coll = array_merge($coll, $p);
            }
            return $coll;
        }

        if( !isset($obj['coordinates']) )
            return false;
        $coordsrc = $obj['coordinates'];
        $coords = array();

        if( $type == 'Point' ) {
            $coords[] = array(array($coordsrc[1], $coordsrc[0]));
        } elseif( $type == 'MultiPoint' ) {
            foreach( $coordsrc as $coord )
                $coords[] = array(array($coordsrc[1], $coordsrc[0]));
        } elseif( $type == 'LineString' ) {
            $coords[] = $this->swap_latlon($coordsrc);
        } elseif( $type == 'MultiLineString' ) {
            foreach( $coordsrc as $coord )
                $coords[] = $this->swap_latlon($coord);
        } elseif( $type == 'Polygon' ) {
            $coords[] = $this->swap_latlon($coordsrc[0]);
        } elseif( $type == 'MultiPolygon' ) {
            foreach( $coordsrc as $coord )
                $coords[] = $this->swap_latlon($coord[0]);
        }
        return count($coords) > 0 ? $coords : false;
    }
}

$formats['geojson'] = new GeoJSONFormat();

?>

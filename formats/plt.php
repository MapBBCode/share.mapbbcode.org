<?php

class PLTFormat implements Format {
    public $title = 'PLT';
    public $mime = 'text/plain';
    public $simplify = true;

    public function export( $data ) {
        $title = strlen($data['title']) > 0 ? str_replace(',', ' ', $data['title']) : date('d.m.Y H:i:s');
		if( strpos($title, '"') !== false )
			$title = '"'.str_replace('"', '""', $title).'"';
        if( defined('OZI_CHARSET') )
            $title = iconv('UTF-8', OZI_CHARSET.'//TRANSLIT', $title);
        $out = <<<PLTHEAD
OziExplorer Track Point File Version 2.1
WGS 84
Altitude is in Feet
Reserved 3
0,2,255,$title,0,0,2,8421376
PLTHEAD;
        $pts = array();
        foreach( $data['objs'] as $obj ) {
            if( count($obj['coords']) > 1 ) {
                $t = 1;
                foreach( $obj['coords'] as $c ) {
                    $pts[] = sprintf('%.5f,%.5f,%d,-777.0,,,', $c[0], $c[1], $t);
                    $t = 0;
                }
            }
        }
        $out .= "\n".count($pts)."\n".implode("\n", $pts)."\n";
        return $out;
    }

    public function test( $header ) {
        return preg_match('/^OziExplorer Track Point File Version 2/', $header);
    }

    public function import( $file ) {
        $title = '';
        $objs = array();
        $curobj = array();
        $i = 0;
        while( ($line = fgets($file, 500)) !== false ) {
            $items = explode(',', $line);
            if( ++$i == 5 ) {
                if( count($items) > 5 ) {
                    $title = trim($items[3]);
                    if( defined('OZI_CHARSET') )
                        $title = iconv(OZI_CHARSET, 'UTF-8//IGNORE', str_replace(chr(209), ',', $title));
					if( substr($title, 0, 1) == '"' && substr($title, strlen($title) - 1, 1) == '"' )
						$title = str_replace('""', '"', substr($title, 1, strlen($title) - 2));
                }
            } elseif( $i > 5 && count($items) > 5 ) {
                $lat = $items[0];
                $lon = $items[1];
                $newpath = $items[2];
                if( is_numeric($lat) && is_numeric($lon) && is_numeric($newpath) ) {
                    $coord = array((float)$lat, (float)$lon);
                    if( $newpath == 1 ) {
                        if( count($curobj) > 1 )
                            $objs[] = array( 'coords' => $curobj );
                        $curobj = array();
                    }
                    $curobj[] = $coord;
                }
            }
        }
        if( count($curobj) > 1 )
            $objs[] = array( 'coords' => $curobj );
        $data = array('objs' => $objs);
        if( strlen(trim($title)) > 0 )
            $data['title'] = trim($title);
        return $data;
    }
}

$formats['plt'] = new PLTFormat();

?>

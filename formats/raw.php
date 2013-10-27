<?php

class RawFormat implements Format {
    public $mime = 'text/plain';

    public function export( $data ) {
        $res = array_to_mapbbcode($data);
        if( isset($data['title']) )
            $res .= str_replace('[/map]', '[ /map]', $data['title']);
        return $res;
    }

    public function test( $header ) {
        return preg_match('/^\s*\[map/', $header);
    }

    public function import( $file ) {
        $content = fread($file, 50000);
        return $this->import_str($content);
    }

    public function import_str( $str ) {
        $bbpos = strpos($str, '[/map]');
        if( $bbpos === false ) {
            $title = $str;
            $bbcode = '';
        } else {
            $title = substr($str, $bbpos + 6);
            $bbcode = substr($str, 0, $bbpos + 6);
        }
        $res = mapbbcode_to_array($bbcode);
        if( strlen($title) > 0 )
            $res['title'] = str_replace('[ /map]', '[/map]', $title);
        return $res;
    }
}

$formats['raw'] = new RawFormat();

?>

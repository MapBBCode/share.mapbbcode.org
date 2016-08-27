<?php

class IFrameCodeFormat implements Format {
    public $title = 'IFRAME';
    public $mime = 'text/html';
    public $can_attach = false;

    public function export( $data ) {
        global $scodeid, $base_path;

        $s = isset($scodeid) ? 
            '<iframe src="'.$base_path.'/'.$scodeid.'?format=iframe&direct' :
            '<iframe src="'.$base_path.'/?format=iframe&direct&bbcode='.array_to_mapbbcode($data);
        $f = '" width="600" height="400" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>';
        $iframe = $s.$f;

        $html = 
'<!DOCTYPE html><html><head><title>IFRAME export</title><meta charset="utf-8"></head><body>'.
'<p>If you would like to link to this route, just copy the code and past it into your source code:</p>'.
'<p><code>'.htmlspecialchars($iframe).'</code></p>'.
'</body></html>';
        return $html;
    }

    public function test( $header ) {
        return false;
    }

    public function import( $file ) {
    }
}

$formats['iframecode'] = new IFrameCodeFormat();

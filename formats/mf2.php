<?php

class MF2Format implements Format {
    public $title = 'mf2';
    public $mime = 'text/html';
    public $ext = 'html';

    public function export( $data ) {
        $out = '';
        foreach( $data['objs'] as $obj ) {
            // Skip polygons and lines since they don't have a good microformats representation
            if( count($obj['coords']) == 1 ) {
                foreach( $obj['coords'] as $coord ) {
                    $out .= '<p class="h-geo">' . "\n";
                    if( isset($obj['text']) ) {
                        $out .= "\t" . '<span class="p-name p-summary">' . htmlspecialchars($obj['text']) . '</span>' . "\n";
                    }
                    $out .= "\t" . '<data class="p-latitude">' . $coord[0] . '</data>' . "\n";
                    $out .= "\t" . '<data class="p-longitude">' . $coord[1] . '</data>' . "\n";
                    $out .= '</p>' . "\n";
                }
            }
        }
        return $out;
    }

    public function test( $header ) {
        return false;
    }

    public function import( $file ) {
    }
}

$formats['mf2'] = new MF2Format();

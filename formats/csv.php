<?php

class CSVFormat implements Format {
    public $title = 'CSV';
    public $mime = 'text/csv';
    public $priority = -10; // last

    public function export( $data ) {
        $out = '';
        foreach( $data['objs'] as $obj ) {
            if( count($obj['coords']) == 1 || count($data['objs']) == 1 ) {
                foreach( $obj['coords'] as $coord ) {
                    $out .= number_format($coord[0], 5, '.', '').','.number_format($coord[1], 5, '.', '').',';
                    if( isset($obj['text']) ) {
                        $out .= '"'.str_replace('"', '""', $obj['text']).'"';
                    }
                    $out .= "\n";
                }
            }
        }
        $out = strlen($out) > 0 ? "latitude,longitude,description\n".$out : '';
        return $out;
    }

    public function test( $header ) {
        $lines = preg_split('/[\r\n]+/', $header);
        if( count($lines) > 3 )
            array_pop($lines); // header is most likely incomplete
        return $this->find_format($lines) !== false;
    }

    public function import( $file ) {
        // cache some lines to determine CSV format
        $lines = array();
        for( $i = 0; $i < 20; $i++ ) {
            $line = fgets($file, 1000);
            if( $line )
                $lines[] = $line;
        }
        $format = $this->find_format($lines);
        //print_r($format);
        if( $format === false )
            return false;
        $i = count($lines);
        while(true) {
            $line = $i > 0 ? $lines[--$i] : fgets($file, 1000);
            if( $line === false )
                break;
            $fields = $this->explode_quoted($format['separator'], $line);
            if( count($fields) != $format['fields'] )
                continue;
            // remove quotes and trim
            for( $j = 0; $j < count($fields); $j++ ) {
                if( preg_match('/^\s*"?(.*?)"?\s*$/', $fields[$j], $m) )
                    $fields[$j] = str_replace('""', '"', $m[1]);
            }
            $lat = str_replace($format['decimal'], '.', $fields[$format['latpos']]);
            $lon = str_replace($format['decimal'], '.', $fields[$format['lonpos']]);
            if( is_numeric($lat) && is_numeric($lon) ) {
                $obj = array('coords' => array(array((float)$lat, (float)$lon)));
                if( isset($format['titlepos']) && strlen($fields[$format['titlepos']]) > 0 )
                    $obj['text'] = $fields[$format['titlepos']];
                $objs[] = $obj;
            }
        }
        return count($objs) > 0 ? array('objs' => $objs) : false;
    }

    // $lines should contain at least 2 lines (the more the better). Returns array of data or false
    private function find_format( $lines_src ) {
        $lines = array();
        foreach( $lines_src as $line )
            if( strlen(rtrim($line)) > 0 )
                $lines[] = rtrim($line);
        if( count($lines) < 2 )
            return;

        // we just expect number of fields to be constant and not less that 2, taking maximum
        // also we expect at least two constant numeric fields
        $delimiters = array(',', ';', "\t");
        $decimals = array('.', ',');
        $maxfields = 0;
        foreach( $decimals as $dec ) {
            foreach( $delimiters as $delim ) {
                $fields = 0;
                $numeric = array();
                $i = 0;
                foreach( $lines as $line ) {
                    $f = $this->explode_quoted($delim, $line);
                    $cnt = count($f);
                    // check 1: field count is constant
                    if( !$fields )
                        $fields = $cnt;
                    elseif( $fields != $cnt ) {
                        $fields = 0;
                        break;
                    }

                    if( ++$i > 1 ) {
                        // check 2: positions of numeric fields are constant (save for line 1)
                        // we expect two consequentical numeric fields with at least 0.01 precision (100 m)
                        for( $j = 0; $j < $cnt; $j++ ) {
                            $isnum = preg_match('/^\s*"?-?\d+'.($dec == '.' ? '\.' : $dec).'\d{2,}"?\s*$/', $f[$j]);
                            if( $i == 2 ) {
                                // create hash
                                if( $isnum )
                                    $numeric[$j] = 1;
                            } else {
                                if( isset($numeric[$j]) && !$isnum )
                                    unset($numeric[$j]);
                            }
                        }
                    }
                }
                $llpos = -1;
                while( ++$llpos + 1 < $fields ) {
                    if( isset($numeric[$llpos]) && isset($numeric[$llpos+1]) )
                        break;
                }
                if( $llpos + 1 < $fields && $fields > $maxfields ) {
                    $maxfields = $fields;
                    $maxdelim = $delim;
                    $maxdec = $dec;
                    $maxllpos = $llpos;
                }
            }
        }
        if( !$maxfields )
            return false;

        // we now have correct $maxdelim, $maxdec, $maxfields and $maxllpos
        // check the first line: if it is a header, then in $maxllpos there might be a hint at "lon,lat" order
        $header = $this->explode_quoted($maxdelim, $lines[0]);
        if( stripos($header[$maxllpos], 'lon') === false ) {
            $latpos = $maxllpos;
            $lonpos = $maxllpos + 1;
        } else {
            $lonpos = $maxllpos;
            $latpos = $maxllpos + 1;
        }

        $result = array(
            'separator' => $maxdelim,
            'decimal' => $maxdec,
            'fields' => $maxfields,
            'latpos' => $latpos,
            'lonpos' => $lonpos
        );

        if( $maxfields > 2 ) {
            if( $maxfields == 3 ) {
                $titlepos = $maxllpos == 0 ? 2 : 0;
            } else {
                // to find a correct title column, we need to check types of all remaining columns
                $emptycount = array_fill(0, $maxfields, 0);
                $integers = array_fill(0, $maxfields, 2);
                $maxlength = array_fill(0, $maxfields, 0);
                $i = 0;
                foreach( $lines as $line ) {
                    if( ++$i == 1 )
                        continue; // skip possible header
                    $fields = $this->explode_quoted($maxdelim, $line);
                    for( $j = 0; $j < count($fields); $j++ ) {
                        $field = $fields[$j];
                        if( preg_match('/^\s*"?(.*?)"?\s*$/', $field, $m) )
                            $field = $m[1]; // remove quotes and trim
                        $isfloat = preg_match('/^-?\d+'.($maxdelim == '.' ? '\.' : $maxdelim).'\d+$/', $field);
                        $isint = preg_match('/^-?\d+$/', $field);
                        $length = strlen($field);
                        if( $length == 0 ) {
                            $emptycount[$j]++;
                        } elseif( $isint && !$isfloat )
                            $intcount[$j]++;
                            if( $integers[$j] == 2 )
                                $integers[$j] = 1;
                        elseif( !$isint && !$isfloat ) {
                            $integers[$j] = 0;
                            if( $length > $maxlength[$j] )
                                $maxlength[$j] = $length;
                        }
                    }
                }
                // sort columns by max length (sorry for O(N²), but N is very low)
                $longest = array();
                for( $j = 0; $j < $maxfields; $j++ ) {
                    $maxidx = -1;
                    $maxlen = 0;
                    for( $k = 0; $k < $maxfields; $k++ ) {
                        if( $maxlength[$k] > $maxlen ) {
                            $maxidx = $k;
                            $maxlen = $maxlength[$k];
                        }
                    }
                    if( $maxidx >= 0 ) {
                        $longest[] = $maxidx;
                        $maxlength[$maxidx] = -1;
                    } else
                        break;
                }

                // now check all columns longest to shortest and select the first which has few empty values and not lat/lon/id
                $isfirstid = $integers[0] == 1;
                $pretendent = -1;
                $maxempty = floor(count($lines) / 2);
                for( $j = 0; $j < count($longest); $j++ ) {
                    $idx = $longest[$j];
                    if( (!$isfirstid || $idx > 0) && $idx != $latpos && $idx != $lonpos ) {
                        if( $emptycount[$idx] <= $maxempty ) {
                            $titlepos = $idx;
                            break;
                        } else
                            $pretendent = $idx;
                    }
                }
                if( !isset($titlepos) && $pretendent >= 0 )
                    $titlepos = $pretendent;
            }
            $result['titlepos'] = $titlepos;
        }

        return $result;
    }

    // like explode(), but treats text in quotes as a whole (and removes quotes).
    // extra spaces are not preserved.
    private function explode_quoted($delim, $line) {
        $result = array();
        $len = strlen($line);
        $start = 0;
        while( $start < $len ) {
            while( $start < $len && $line[$start] === ' ' ) {
                $start++;
            }
            if( $start < $len && $line[$start] === '"' ) {
                $quotes = $start;
                $start++;
            } else
                $quotes = -1;
            // we found the first character
            $end = $start; // last non-whitespace char
            $cur = $end; // current char that we are testing
            $after_quote = $quotes < 0 ? true : false;
            while( $cur < $len ) {
                if( $line[$cur] === $delim && $after_quote )
                    break;
                if( $line[$cur] === '"' ) {
                    $cur++;
                    if( $line[$cur] === '"' ) {
                        $end = $cur;
                        $cur++;
                    } elseif( !$after_quote ) {
                        $end = $cur - 2;
                        $after_quote = true;
                    }
                } else {
                    if( $line[$cur] !== ' ' )
                        $end = $cur;
                    $cur++;
                }
            }
            // ok, now cut the value
            $result[] = $start < $len ? substr($line, $start, $end-$start+1) : '';
            // next entry (cur points at a delimiter)
            $start = $cur + 1;
            if( $start == $len )
                $result[] = ''; // empty value at the tail
        }
        return $result;
    }
}

$formats['csv'] = new CSVFormat();

?>

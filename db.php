<?php

// creates $db object if necessary and returns it
function getdb() {
    global $db;
    if( isset($db) )
        return $db;
    if( !db_available() )
        return false;

    $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
    if( $db->connect_errno )
        die('Cannot connect to database: ('.$db->connect_errno.') '.$db->connect_error);
    $db->set_charset('utf8');
    return $db;
}

// the service can work without a database
function db_available() {
    return defined('DB_DATABASE') && strlen(DB_DATABASE) > 0;
}

// create tables if they do not exist
function initdb() {
    global $message;
    $db = getdb();
    if( !$db ) {
        $message = 'Please specify database in the config.php';
        return;
    }
    $table = DB_TABLE;
    $res = $db->query("show tables like '$table'");
    if( $res->num_rows < 1 ) {
        $sql = <<<CSQL
CREATE TABLE $table (
    codeid varchar(10) not null primary key,
    editid varchar(10) not null,
    title varchar(250) not null,
    created datetime not null,
    updated datetime not null,
    bbcode text not null
) DEFAULT CHARACTER SET utf8
CSQL;
        $res = $db->query($sql);
        if( $res ) {
            $table = DB_TABLE.'_users';
            $sql = <<<CSQL2
CREATE TABLE $table (
    codeid varchar(10) not null,
    userid varchar(250) not null,
    editable tinyint(1) not null
) DEFAULT CHARACTER SET utf8
CSQL2;
            $res = $db->query($sql);
        }
        if( !$res )
            $message = 'Failed to create table '.$table.': '.$db->error;
        else
            $message = 'Tables have been created successfully';
    } else
        $message = "Table '$table' already exists";
}

// returns partial data for codeid (without dates). Caches requests
function get_data( $codeid ) {
    if( !preg_match('/^[a-z]+$/', $codeid) )
        return;

    $data = cache_fetch($codeid, 'code');
    if( $data )
        return $data;

    $db = getdb();
    if( !$db )
        return false;
    $res = $db->query('select editid, title, bbcode from '.DB_TABLE." where codeid = '$codeid'");
    $assoc = $res->num_rows > 0 ? $res->fetch_assoc() : false;
    $res->free();
    cache_put($codeid, 'code', $assoc);
    cache_purge(); // since we've accessed the db, why not purge the cache?
    return $assoc;
}

// find entry with gived id in cache
function cache_fetch( $id, $prefix ) {
    $filename = 'cache/'.$prefix.'_'.preg_replace('/[^a-z0-9_-]/', '', strtolower($id));
    $result = @file_get_contents($filename);
    if( $result !== false ) {
        $decoded = @unserialize($result);
        if( is_array($decoded) )
            return $decoded;
    }
    return false;
}

// put entry into cache
function cache_put( $id, $prefix, $data ) {
    if( !is_array($data) )
        return false;
    $filename = 'cache/'.$prefix.'_'.preg_replace('/[^a-z0-9_-]/', '', strtolower($id));
    @file_put_contents($filename, serialize($data));
}

// removes specific entry from cache
function cache_remove( $id, $prefix ) {
    if( $id ) {
        $idfile = 'cache/'.$prefix.'_'.preg_replace('/[^a-z0-9_-]/', '', strtolower($id));
        if( @is_file($idfile) ) {
            @unlink($idfile);
        }
    } elseif( $prefix ) {
        // remove all entries with that prefix
        $files = @scandir('cache');
        if( $files !== false ) {
            $p = $prefix.'_';
            foreach( $files as $f ) {
                if( substr($f, 0, strlen($p)) == $p )
                    @unlink('cache/'.$f);
            }
        }
    }
}

// removes old entries in cache
function cache_purge() {
    $files = @scandir('cache');
    // note: we add a little threshold to not remove a file at a time
    if( $files !== false && count($files) > MAX_CACHED + 50 ) {
        // sort by descending mtime
        $sorted = array();
        foreach($files as $f ) {
            $filename = 'cache/'.$f;
            if( @is_file($filename) ) {
                $sorted[filemtime($filename)] = $filename;
            }
        }
        krsort($sorted);
        $i = 0;
        foreach( $sorted as $t => $filename ) {
            if( $i++ >= MAX_CACHED )
                @unlink($filename);
        }
    }
}

?>

<?php

// creates $db object if necessary and returns it
function getdb() {
    global $db;
    if( isset($db) )
        return $db;
    $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
    if( $db->connect_errno )
        die('Cannot connect to database: ('.$db->connect_errno.') '.$db->connect_error);
    $db->set_charset('utf8');
    return $db;
}

// create tables if they do not exist
function initdb() {
    global $message;
    $db = getdb();
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

    $data = cache_fetch($codeid);
    if( $data )
        return $data;

    $db = getdb();
    $res = $db->query('select editid, title, bbcode from '.DB_TABLE." where codeid = '$codeid'");
    $assoc = $res->num_rows > 0 ? $res->fetch_assoc() : false;
    $res->free();
    cache_put($codeid, $assoc);
    cache_purge(); // since we've accessed the db, why not purge the cache?
    return $assoc;
}

// find entry with gived id in cache
function cache_fetch( $id ) {
    $filename = 'cache/'.preg_replace('/[^a-z0-9_-]/', '', strtolower($id));
    $result = @file_get_contents($filename);
    if( $result !== false ) {
        $decoded = @unserialize($result);
        if( is_array($decoded) )
            return $decoded;
    }
    return false;
}

// put entry into cache
function cache_put( $id, $data ) {
    if( !is_array($data) )
        return false;
    $filename = 'cache/'.preg_replace('/[^a-z0-9_-]/', '', strtolower($id));
    @file_put_contents($filename, serialize($data));
}

// removes specific entry from cache
function cache_remove( $id ) {
    if( $id ) {
        $idfile = 'cache/'.preg_replace('/[^a-z0-9_-]/', '', strtolower($id));
        if( @is_file($idfile) ) {
            @unlink($idfile);
        }
    }
}

// removes old entries in cache
function cache_purge() {
    $files = @scandir('cache');
    if( $files !== false && count($files) > MAX_CACHED ) {
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

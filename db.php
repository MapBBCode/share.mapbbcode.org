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

    // todo: caching
    $db = getdb();
    $res = $db->query('select editid, title, bbcode from '.DB_TABLE." where codeid = '$codeid'");
    $assoc = $res->num_rows > 0 ? $res->fetch_assoc() : false;
    $res->free();
    return $assoc;
}

// removes old entries in cache and an entry for $codeid if specified
function purge_cache( $codeid ) {
}

?>

<?php
    const DB_HOST = 'localhost';
    const DB_USER = '';
    const DB_PASSWORD = '';
    const DB_DATABASE = ''; // if empty, db is not used (increase MAX_CACHED then)
    const DB_TABLE = 'sharemap'; // for other tables this is the prefix
    const NEED_INIT_DB = true; // set to false after calling /initdb
    const BING_KEY = '' // put your bing imagery key here
    const MAX_CACHED = 500; // number of files in cache directory
    const HASH_LENGTH = 5; // code hash
    const EDIT_HASH_LENGTH = 5; // hash for editing
    const MAX_IMPORT_NODES = 200; // nodes in imported gps traces
    const MAX_TOTAL_NODES = 1000; // total nodes in all imported gps traces
    const IMPORT_SINGLE = false; // clear map on import
    //const MOD_REWRITE = true; // set to explicit value if autodetection is wrong
    const OZI_CHARSET = 'CP1251'; // charset for OziExplorer formats, UTF-8 by default

    // set this to your timezone or whatever (doesn't really matter)
    date_default_timezone_set('UTC');
?>

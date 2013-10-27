<?php
    const DB_HOST = 'localhost';
    const DB_USER = '';
    const DB_PASSWORD = '';
    const DB_DATABASE = '';
    const DB_TABLE = 'sharemap'; // for other tables this is the prefix
    const NEED_INIT_DB = true; // set to false after calling /initdb
    const HASH_LENGTH = 5; // code hash
    const EDIT_HASH_LENGTH = 5; // hash for editing
    const MAX_IMPORT_NODES = 200; // nodes in imported gps traces
    const MAX_TOTAL_NODES = 1000; // total nodes in all imported gps traces
?>

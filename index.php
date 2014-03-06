<?php
define('IN_MAPBBCODE', 1);
define('VERSION', '1.2-13');
require('config.php');
require('convert.php');
require('db.php');

define('SCRIPT_NAME', 'index.php');
if( !defined('MOD_REWRITE') ) {
	if( function_exists('apache_get_modules') )
		define('MOD_REWRITE', in_array('mod_rewrite', apache_get_modules()) && file_exists('.htaccess'));
	else
		define('MOD_REWRITE', true); // true for nginx
}

$doc_path = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '').'://'.
    $_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$base_path = MOD_REWRITE ? $doc_path : $doc_path.'/'.SCRIPT_NAME;

ini_set('session.gc_maxlifetime', 7776000);
ini_set('session.cookie_lifetime', 7776000);
session_set_cookie_params(7776000);
session_start();

//if( $_SERVER['HTTP_HOST'] == 'localhost' ) $userid = 'test';

if( isset($_SESSION['user_id']) )
    $userid = $_SESSION['user_id'];

$message = '';
$api = isset($_REQUEST['api']);
$params = parse_params();
$action = $params['action'];
$bbcode = $params['bbcode'];
$title = $params['title'];

if( isset($params['id']) )
    $scodeid = $params['id'];
if( isset($params['editid']) )
    $seditid = $params['editid'];

$data = false;
if( isset($scodeid) ) {
    $data = get_data($scodeid);
    if( !$data ) {
        if( $api )
            return_json(array('error' => 'No such code: '.$scodeid));
        $message = 'There is no code in the database with given id';
    }
}

if( $action == '' && $data && !$params['post'] ) {
    // only for GET requests with id (that is, not ?bbcode=)
    $bbcode = $data['bbcode'];
    $title = $data['title'];

    if( $api )
        return_json(array('title' => $title, 'bbcode' => $bbcode));

} elseif( $action == '' && isset($_GET['gz']) && strlen($_GET['gz']) > 12 ) {
    // action is base64-encoded bbcode+title
    $res = decompress_bbcode($_GET['gz']);
    if( $res && count($res) == 2 && strlen($res[1]) > 10 ) {
        $title = $res[0];
        $bbcode = $res[1];
    }
}

if( $data && isset($params['key']) && $params['key'] == $data['editid'] ) {
    $newcode = false;
    $seditid = $params['key'];
    $message = '<b><a href="'.$base_path.'/'.$params['id'].'" target="mapbbstatic">Share this link</a></b> for read-only view of this map.<br><a href="'.$base_path.'/'.$params['id'].'/'.$params['key'].'">Bookmark this</a> to edit the map later';
    $nohide = 1; // do not hide message
    if( isset($userid) ) {
        update_library($userid, $scodeid, $seditid);
        $message .= ' (or check the library)';
    } elseif( db_available() )
        $message .= ' (sign in to have it stored for you automatically)';
}

$editing = $params['post'] || isset($seditid) || (strlen($bbcode) == 0 && strlen($title) == 0);
$newcode = !isset($seditid);

// see also $actions array in parse_surl
if( $action == 'initdb' && NEED_INIT_DB ) {
    initdb();

} elseif( $action == 'fmtlist' ) {
    $fmtdesc = get_format_arrays();
    return_json($fmtdesc);

} elseif( $action == 'save' ) {
    save($params, $data);

} elseif( $action == 'signout' ) {
    unset($_SESSION['user_id']);
    unset($userid);

} elseif( $action == 'bookmark' && isset($userid) && $data ) {
    update_library($userid, $params['id'], '');
    $message = 'The code was added to your library';

} elseif( $action == 'import' && isset($_FILES['file']) ) {
    if( !is_uploaded_file($_FILES['file']['tmp_name']) || $_FILES['file']['size'] == 0 || $_FILES['file']['error'] > 0 ) {
        $errors = array('OK', 'too big', 'bigger than MAX_FILE_SIZE', 'partial upload', 'no file', '', 'nowhere to store', 'failed to write', 'extension error');
        $err = $errors[$_FILES['file']['error']];
        if( $api )
            return_json(array('error' => 'File upload error: '.$err, 'code' => $_FILES['file']['error']));
        else
            $message = 'File upload error ('.$err.')';
    } else {
        $titlebb = defined('IMPORT_SINGLE') && !IMPORT_SINGLE ? array($title, $bbcode) : array('', '');
        $titlebb = import($_FILES['file']['tmp_name'], $titlebb);
        $title = $titlebb[0];
        $bbcode = $titlebb[1];
        if( $api )
            return_json(array('title' => $title, 'bbcode' => $bbcode));
    }
}

if( isset($_REQUEST['format']) && preg_match('/^[a-z]+$/', $_REQUEST['format']) ) {
    $format = $_REQUEST['format'];
    header('Access-Control-Allow-Origin: *');
    $result = export($format, $title, $bbcode, isset($scodeid) ? $scodeid : '', !isset($_REQUEST['direct']));
    if( $result == CONVERT_OK )
        exit;
    if( $result == CONVERT_NOT_SUPPORTED ) {
        if( $action == 'export' )
            $message = 'Unknown or unimplemented export type: '.$format;
        else {
            header('HTTP/1.1 415 Format Not Suppoprted');
            exit;
        }
    } elseif( $result == CONVERT_EMPTY ) {
        if( $action == 'export' )
            $message = 'Output file is empty';
        else {
            header('HTTP/1.1 400 No Data In BBCode');
            exit;
        }
    }
}

$fmtdesc = get_format_arrays();

if( !$editing && strlen($bbcode) < 11 )
    $bbcode = '[map][/map]';

if( isset($userid) )
    $library = fetch_library($userid);

require('page.php');

// ---------------------------------- FUNCTIONS ----------------------------

// print data as json(p) and exit
function return_json( $data ) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    $json = json_encode($data);
    if( isset($_REQUEST['jsonp']) )
        print $_REQUEST['jsonp']."($json);";
    else
        print $json;
    exit;
}

// save bbcode to database (or update)
function save( $params, $data ) {
    global $message, $api, $base_path;
    $title = $params['title'];
    $bbcode = $params['bbcode'];

    if( strlen($title) < 3 && strlen($bbcode) < 7 ) {
        if( $api )
            return_json(array('error' => 'Would not save empty data, sorry'));
        else
            $message = 'Would not save empty data, sorry';
        return;
    }

    if( strlen($title) > 250 ) {
        $spacepos = strrpos($title, ' ');
        $title = substr($title, 0, $spacepos !== false ? $spacepos : 250);
    }

    $db = getdb();
    if( $data && isset($params['key']) && $data['editid'] == $params['key'] ) {
        // update
        $codeid = $params['id'];
        $editid = $params['key'];
        $sql = !$db ? '' : "update ".DB_TABLE." set updated=now(), title='".$db->escape_string($title)."', bbcode='".$db->escape_string($bbcode)."' where codeid = '$codeid'";
        cache_remove($codeid, 'code');
        cache_remove(false, 'user'); // yup, now a lot of users can have their libraries updated
    } else {
        $editid = generate_id(EDIT_HASH_LENGTH);
        $tries = 10;
        do {
            $codeid = generate_id(HASH_LENGTH);
            $exists = get_data($codeid) !== false;
        } while( $exists );
        $sql = !$db ? '' : "insert into ".DB_TABLE." (created, updated, codeid, editid, title, bbcode) values(now(), now(), '$codeid', '$editid', '".$db->escape_string($title)."', '".$db->escape_string($bbcode)."')";
    }

    if( $db ) {
        $res = $db->query($sql);
    } else {
        // put code to cache
        $assoc = array('editid' => $editid, 'title' => $title, 'bbcode' => $bbcode);
        cache_put($codeid, 'code', $assoc);
        $res = true;
    }
    if( !$api ) {
        if( !$res ) {
            $message = 'Failed to insert entry in the database: '.$db->error;
        } else {
            header("Location: ".$base_path."/$codeid/$editid");
            exit;
        }
    } else {
        if( !$res ) {
            return_json(array('error' => 'Failed to insert entry in the database: '.$db->error));
        } else {
            return_json(array('codeid' => $codeid, 'editid' => $editid, 'viewurl' => $base_path."/$codeid", 'editurl' => $base_path."/$codeid/$editid"));
        }
        exit;
    }
}

// Adds code id to the user's library. Edit id is checked to test if user can edit code
function update_library( $userid, $codeid, $editid ) {
    $db = getdb();
    if( !$db )
        return;
    $uidesc = $db->escape_string($userid);
    $sql = 'select editable from '.DB_TABLE."_users where codeid = '$codeid' and userid = '$uidesc'";
    $res = $db->query($sql);
    if( !$res )
        return;
    $cnt = $res->num_rows;
    $row = $res->fetch_row();
    $editable = $row[0];
    $res->free();
    if( !$cnt ) {
        $sql = 'insert into '.DB_TABLE."_users (userid, codeid, editable) values ('$uidesc', '$codeid', ".(isset($editid) && strlen($editid) > 0 ? 1 : 0).")";
        $db->query($sql);
    } elseif( !$editable && isset($editid) && strlen($editid) > 0 ) {
        $sql = 'update '.DB_TABLE."_users set editable=1 where userid='$uidesc' and codeid='$codeid'";
        $db->query($sql);
    }
    cache_remove($userid, 'user');
}

// Removes a bookmark from user's library
function remove_bookmark( $userid, $codeid ) {
    $db = getdb();
    if( !$db )
        return false;
    $uidesc = $db->escape_string($userid);
    $sql = 'delete from '.DB_TABLE."_users where codeid = '$codeid' and userid = '$uidesc'";
    $res = $db->query($sql);
    if( $res && $db->affected_rows > 0 ) {
        cache_remove($userid, 'user');
        return true;
    }
    return false;
}

// Returns as array of all library entries sorted by update time
function fetch_library( $userid ) {
    $stored = cache_fetch($userid, 'user');
    if( $stored !== false )
        return $stored;

    global $message;
    $codes = array();
    $db = getdb();
    if( !$db )
        return $codes;
    $sql = 'select now() as now, m.*, u.editable from '.DB_TABLE.' m, '.DB_TABLE.'_users u where u.codeid = m.codeid and u.userid = \''.$db->escape_string($userid).'\' order by m.updated desc limit 100';
    $res = $db->query($sql);
    if( $res ) {
        require_once('mapbbcode.php');
        while( $row = $res->fetch_assoc() ) {
            $item = array();
            $item['codeid'] = $row['codeid'];
            $item['editid'] = $row['editable'] ? $row['editid'] : '';
            $item['created'] = human_date($row['created'], $row['now']);
            $item['updated'] = human_date($row['updated'], $row['now']);
            $item['title'] = $row['title'];
            $item['stats'] = mapbbcode_stats($row['bbcode']);
            $codes[] = $item;
        }
        $res->free();
        cache_put($userid, 'user', $codes);
    } else
        $message = 'Failed to retrieve user library: '.$db->error;
    return $codes;
}

// Generated random id with given length
function generate_id($length) {
    $id = '';
    for( $i = 0; $i < $length; $i++ )
        $id .= chr(mt_rand(97, 122)); // 'a'..'z'
    return $id;
}

// Arhives bbcode and title
function compress_bbcode($title, $bbcode) {
    $base64 = base64_encode(gzdeflate($bbcode.str_replace('[/map]', '[ /map]', $title), 9));
    $firsteq = strpos($base64, '=');
    return strtr($firsteq === false ? $base64 : substr($base64, 0, $firsteq), '+/', '-_');
}

// Unarchives bbcode and title
function decompress_bbcode($str) {
    $base64 = strtr($str, '-_', '+/');
    while( strlen($base64) % 4 )
        $base64 .= '=';
    $titlebb = gzinflate(base64_decode($base64), 20000);
    if( $titlebb === false )
        return false;
    $bbpos = strpos($titlebb, '[/map]');
    if( $bbpos === false ) {
        $title = $titlebb;
        $bbcode = '';
    } else {
        $title = substr($titlebb, $bbpos + 6);
        $bbcode = substr($titlebb, 0, $bbpos + 6);
    }
    return array(str_replace('[ /map]', '[/map]', $title), $bbcode);
}

// Screens \ and ' characters (for javascript strings)
function screen_param($str) {
    return str_replace(array("\\", "'"), array("\\\\", "\\'"), $str);
}

// Converts mysql date to human-readable string
function human_date($sqldate, $sqlnow = false) {
    $now = $sqlnow ? strtotime($sqlnow) : time();
    $date = strtotime($sqldate);
    $diff = round(($now - $date) / 60); // in minutes
    //echo "($sqldate) ".date('Y-m-d H:i:s', $date).' -> '.date('Y-m-d H:i:s', $now)." = $diff<br>";
    if( $diff < 0 )
        return 'in the future: '.$diff;
    if( $diff == 0 ) return 'just now';
    if( $diff == 1 ) return 'a minute ago';
    if( $diff < 55 ) return $diff.' minutes ago';
    $hours = round($diff/60);
    $curhour = (int)date('H', $now);
    if( $hours == 1 ) return 'an hour ago';
    if( $hours <= max(12, $curhour + 1) ) return $hours.' hours ago';
    // now test real difference in dates
    $curday = floor(strtotime(date('Y-m-d', $now)) / 86400);
    $day = floor(strtotime(date('Y-m-d', $date)) / 86400);
    if( $curday - $day == 1 ) return 'yesterday';
    // if( $curday - $day == 2 ) return 'two days ago';
    // now output real dates
    //$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    $months = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', '');
    $month = $months[(int)idate('m', $date)];
    if( date('Y', $now) == date('Y', $date) )
        return $month.' '.idate('d', $date);
    else
        return $month.' '.date('Y', $date);
}

// ---------------------------------- URL PARSING ----------------------------

// returns URL for those three parameters
function build_url( $action, $codeid = '', $editid = '' ) {
    global $base_path;
    $url = $base_path;
    if( strlen($codeid) > 0 ) {
        $url .= '/'.$codeid;
        if( strlen($editid) > 0 )
            $url .= '/'.$editid;
    }
    if( strlen($action) > 0 )
        $url .= '?'.$action;
    return $url;
}

// returns array(action, id?, key?)
function parse_surl() {
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    if(function_exists('apache_request_headers'))
        $url = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['PHP_SELF'];
    else
        $url = $_SERVER['REQUEST_URI']; // under nginx, path info is in REQUEST_URI

    if( substr($url, 0, strlen($base_path)) == $base_path )
        $url = substr($url, strlen($base_path));
    if( substr($url, 1, strlen(SCRIPT_NAME)) == SCRIPT_NAME )
        $url = substr($url, strlen(SCRIPT_NAME) + 1);

    $result = array('action' => '');
    $actions = array('initdb', 'fmtlist', 'save', 'signout', 'bookmark', 'import', 'export');
    if( preg_match('#^/?([a-z]+)(?:/([a-z]+))?/?#', $url, $m) ) {
        $result[in_array($m[1], $actions) ? 'action' : 'id'] = $m[1];
        $result['key'] = count($m) > 2 ? $m[2] : '';
    }
    return $result;
}

// returns array(action, id?, key?, codeid?, editid?, title, bbcode)
function parse_params() {
    $result = parse_surl();
    if( isset($_POST['codeid']) && preg_match('/^[a-z]+$/', $_POST['codeid']) && (strlen($_POST['codeid']) == HASH_LENGTH || strlen($_POST['codeid'] == 4)) ) {
        // todo: remove 4 after mid-december?
        $result['codeid'] = $_POST['codeid'];
        $result['id'] = $result['codeid']; // POST overrides GET
    } if( isset($_POST['editid']) && preg_match('/^[a-z]+$/', $_POST['editid']) ) {
        $result['editid'] = $_POST['editid'];
        $result['key'] = $result['editid'];
    }
    $result['title'] = trim(isset($_POST['title']) && strlen($_POST['title']) > 0 ? $_POST['title'] : (!isset($result['codeid']) && isset($_GET['title']) ? $_GET['title'] : ''));
    $result['post'] = isset($_POST['bbcode']) && strlen($_POST['bbcode']) > 0;
    $bbcode = trim($result['post'] ? $_POST['bbcode'] : (!isset($result['codeid']) && isset($_GET['bbcode']) ? $_GET['bbcode'] : ''));
    if( strlen($bbcode) > 0 && (strlen($bbcode) < 8 || (substr($bbcode, 0, 4) != '[map' && substr($bbcode, -6) != '[/map]')) )
        $bbcode = '[map]'.$bbcode.'[/map]';
    $result['bbcode'] = $bbcode;
    return $result;
}

?>

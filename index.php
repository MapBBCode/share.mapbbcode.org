<?php
require('config.php');
require('convert.php');
require('db.php');

ini_set('session.gc_maxlifetime', 7776000);
ini_set('session.cookie_lifetime', 7776000);
session_set_cookie_params(7776000);
session_start();

//if( $_SERVER['HTTP_HOST'] == 'localhost' ) $userid = 'test';

if( isset($_SERVER['REDIRECT_URL']) && preg_match('#^/?([a-zA-Z0-9_-]+)/?(?:/([a-z]+))?$/?#', $_SERVER['REDIRECT_URL'], $m) ) {
    $action = $m[1];
    $aparam = count($m) > 2 ? $m[2] : '';
} else {
    $action = '';
}

if( isset($_SESSION['user_id']) )
    $userid = $_SESSION['user_id'];

$bbcode = '';
$title = '';
$editing = true;
$newcode = true;
$readpost = true;
$fmtdesc = get_format_arrays();
$message = '';
$api = isset($_REQUEST['api']);

if( $action == 'initdb' && NEED_INIT_DB ) {
    initdb();

} elseif( $action == 'fmtlist' ) {
    return_json($fmtdesc);

} elseif( $action == 'signout' ) {
    unset($_SESSION['user_id']);
    unset($userid);

} elseif( $action == 'save' && isset($_POST['bbcode']) && isset($_POST['title']) ) {
    $bbcode = $_POST['bbcode'];
    $title = $_POST['title'];
    save($title, $bbcode);

} elseif( $action == 'import' && isset($_FILES['file']) ) {
    if( !is_uploaded_file($_FILES['file']['tmp_name']) || $_FILES['file']['size'] == 0 || $_FILES['file']['error'] > 0 ) {
        $errors = array('OK', 'too big', 'bigger than MAX_FILE_SIZE', 'partial upload', 'no file', '', 'nowhere to store', 'failed to write', 'extension error');
        $err = $errors[$_FILES['file']['error']];
        if( $api )
            return_json(array('error' => 'File upload error: '.$err, 'code' => $_FILES['file']['error']));
        else
            $message = 'File upload error ('.$err.')';
    } else {
        $titlebb = import($_FILES['file']['tmp_name']);
        $title = $titlebb[0];
        $bbcode = $titlebb[1];
        $readpost = false;
        if( $api )
            return_json(array('title' => $title, 'bbcode' => $bbcode));
    }

} elseif( $action == 'export' && isset($_POST['bbcode']) && isset($_POST['title']) && strlen($aparam) > 0 ) {
    $result = export($aparam, $_POST['title'], $_POST['bbcode'], isset($_POST['codeid']) ? $_POST['codeid'] : '');
    if( $result == CONVERT_OK )
        exit;
    elseif( $result == CONVERT_NOT_SUPPORTED )
        $message = 'Unknown or unimplemented export type: '.$aparam;
    elseif( $result == CONVERT_EMPTY )
        $message = 'Output file is empty';

} elseif( $action == 'bookmark' && isset($userid) && isset($_POST['codeid']) ) {
    if( get_data($_POST['codeid']) ) {
        update_library($userid, $_POST['codeid'], '');
        $message = 'The code was added to your library';
    }

} elseif( strlen($action) == HASH_LENGTH || strlen($action) == 4 ) { // 4 is legacy, todo remove after Nov 30
    // find $action in the table
    $data = get_data($action);
    if( $data ) {
        $bbcode = $data['bbcode'];
        $title = $data['title'];
        $scodeid = $action;

        if( $api )
            return_json(array('title' => $title, 'bbcode' => $bbcode));

        if( $aparam != $data['editid'] ) {
            $editing = false;
        } else {
            $newcode = false;
            $seditid = $aparam;
            $message = '<b><a href="/'.$action.'" target="mapbbstatic">Link for sharing</a></b>. Bookmark this page to alter the map later';
            $nohide = 1; // do not hide message
            if( isset($userid) ) {
                update_library($userid, $scodeid, $seditid);
                $message .= ' (or check the library)';
            }
        }
        $readpost = false;
    } else {
        if( $api )
            return_json(array('error' => 'No such code: '.$action));
        $message = 'There is no code in the database with given id';
    }

} elseif( strlen($action) > 0 && $api ) {
    // very incorrect id
    return_json(array('error' => 'Incorrect code: '.$action));

} elseif( isset($_GET['gz']) && strlen($_GET['gz']) > 12 ) {
    // action is base64-encoded bbcode+title
    $res = decompress_bbcode($_GET['gz']);
    if( $res && count($res) == 2 && strlen($res[1]) > 10 ) {
        $title = $res[0];
        $bbcode = $res[1];
        $editing = false;
    }

} else {
    // title and bbcode parameters
    $title = isset($_GET['title']) ? $_GET['title'] : '';
    if( isset($_GET['bbcode']) ) {
        $bbcode = trim($_GET['bbcode']);
        if( substr($bbcode, 0, 4) != '[map' && substr($bbcode, -6) != '[/map]' )
            $bbcode = '[map]'.$bbcode.'[/map]';
        $editing = false;
    }
}

if( $readpost ) {
    // update fields from POST request
    if( isset($_POST['bbcode']) && strlen($_POST['bbcode']) > 0 ) {
        $bbcode = $_POST['bbcode'];
        $editing = true;
    }
    if( isset($_POST['title']) && strlen($_POST['title']) > 0 )
        $title = $_POST['title'];
    if( isset($_POST['codeid']) && strlen($_POST['codeid']) > 0 )
        $scodeid = $_POST['codeid'];
    if( isset($_POST['editid']) && strlen($_POST['editid']) > 0 ) {
        $seditid = $_POST['editid'];
        $newcode = false;
    }
}

if( isset($_GET['format']) && strlen($_GET['format']) > 0 ) {
    header('Access-Control-Allow-Origin: *');
    $result = export($_GET['format'], $title, $bbcode, isset($scodeid) ? $scodeid : '', false);
    if( $result == CONVERT_NOT_SUPPORTED )
        header('HTTP/1.1 415 Format Not Suppoprted');
    elseif( $result == CONVERT_EMPTY )
        header('HTTP/1.1 400 No Data In BBCode');
    exit;
}

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
function save( $title, $bbcode ) {
    global $message, $api;

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

    $codeid = isset($_POST['codeid']) && isset($_POST['editid']) ? $_POST['codeid'] : '';
    $editid = false;
    if( isset($_POST['codeid']) && strlen($_POST['codeid']) == HASH_LENGTH && preg_match('/^[a-z]+$/', $_POST['codeid']) ) {
        $data = get_data($_POST['codeid']);
        if( $data )
            $editid = $data['editid'];
    }

    $db = getdb();
    if( $editid && $editid == $_POST['editid'] ) {
        // update
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
            header("Location: http://".$_SERVER['HTTP_HOST']."/$codeid/$editid");
            exit;
        }
    } else {
        if( !$res ) {
            return_json(array('error' => 'Failed to insert entry in the database: '.$db->error));
        } else {
            return_json(array('codeid' => $codeid, 'editid' => $editid, 'viewurl' => "http://".$_SERVER['HTTP_HOST']."/$codeid", 'editurl' => "http://".$_SERVER['HTTP_HOST']."/$codeid/$editid"));
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
    $sql = 'select now() as now, m.*, u.editable from '.DB_TABLE.' m, '.DB_TABLE.'_users u where u.codeid = m.codeid and u.userid = \''.$db->escape_string($userid).'\' order by m.updated desc limit 30';
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

?>

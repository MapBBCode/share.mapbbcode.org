<?php
require('openid.php');

$host = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '').'://'.$_SERVER['HTTP_HOST'];
$phpself = $host.rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\').'/auth.php';
$close = false;
try {
    $openid = new LightOpenID($host);
    if( !$openid->mode ) {
        if( isset($_POST['openid_identifier']) ) {
            $openid->identity = $_POST['openid_identifier'];
            header('Location: '.$openid->authUrl());
        } else { ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login to MapBBCode Share with OpenID</title>
    <link type="text/css" rel="stylesheet" href="lib/openid.css" />
    <script type="text/javascript" src="lib/openid-no-framework.js"></script>
    <script type="text/javascript" src="lib/openid-mapbb.js"></script>
    <style type="text/css">
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
        h1 { font-size: 14pt; }
    </style>
</head>

<body>
    <h1>Login to MapBBCode Share with OpenID</h1>
	<form action="<?php echo $phpself ?>" method="post" id="openid_form">
        <input id="openid_form_submit" type="submit" style="display: none;" />
        <div id="openid_choice">
            <p>Please click your account provider:</p>
            <div id="openid_btns"></div>
        </div>
        <div id="openid_input_area">
            <input id="openid_identifier" name="openid_identifier" type="text" value="http://" />
            <input id="openid_submit" type="submit" value="Sign-In"/>
        </div>
        <noscript>
            <p>OpenID is service that allows you to log-on to many different websites using a single indentity.
            Find out <a href="http://openid.net/what/">more about OpenID</a> and <a href="http://openid.net/get/">how to get an OpenID enabled account</a>.</p>
        </noscript>
        <input type="button" value="No, thanks" onclick="window.close();">
    </form>
    <div style="margin-top: 2em;">A note on privacy: only a hash of your identifier is stored in the database, which means no one, not even an administrator, can trace it back to you. The hash is used for storing your library of maps, but no maps can be linked to you personally, even by a person with database access. Only those who have access to your account (or your computer while you are logged in) can see your map library.</div>
    <script>openid.init('openid_identifier');</script>
</body>
</html>
<?php
        }
    } elseif( $openid->mode == 'cancel' ) {
        $message = 'You have cancelled authentication';
    } elseif( !$openid->validate() ) {
        $message = 'Authentication was unsuccessful. Sorry';
    } else {
        $id = $openid->identity;
        ini_set('session.gc_maxlifetime', 7776000);
        ini_set('session.cookie_lifetime', 7776000);
        session_set_cookie_params(7776000);
        session_start();
        $_SESSION['user_id'] = md5($id);
        $message = 'You have logged in as '.$id;
        $close = true;
    }
} catch( ErrorException $e ) {
    $message = 'Error: '.$e->getMessage();
}

if( !isset($message) )
    exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login to MapBBCode Share with OpenID</title>
    <style type="text/css">
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
        h1 { font-size: 14pt; }
    </style>
</head>

<body>
<h1><?=htmlspecialchars($message) ?></h1>
<input type="button" value="Close" onclick="window.close();">
<?php if( $close ) { ?>
<script>
if( window.opener && window.opener.submit )
    window.opener.submit();
window.close();
</script>
<?php } ?>
</body>
</html>

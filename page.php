<!DOCTYPE html>
<html>
<head>
<title><?=isset($title) && strlen($title) > 0 ? htmlspecialchars($title).' — ' : (isset($scodeid) && strlen($scodeid) > 0 ? $scodeid.' — ' : '') ?>MapBBCode Share</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="/lib/leaflet.css" />
<link rel="stylesheet" href="/lib/leaflet.draw.css" />
<!--[if lte IE 8]>
    <link rel="stylesheet" href="/lib/leaflet.ie.css" />
    <link rel="stylesheet" href="/lib/leaflet.draw.ie.css" />
<![endif]-->
<script src="/lib/leaflet.js"></script>
<script src="/lib/leaflet.draw.js"></script>
<script src="/lib/Bing.js"></script>
<script src="/lib/mapbbcode.js"></script>
<script src="/lib/Param.Simplify.js"></script>
<script src="/lib/Param.Measure.js"></script>
<script src="/lib/StaticLayerSwitcher.js"></script>
<style>
    html, body, #mapedit, .leaflet-container { height: 100%; margin: 0; }
    body {
        font-family: Arial, sans-serif;
    }
    #message {
        position: absolute;
        bottom: 20px;
        left: 10px;
        background: yellow;
        opacity: 0.7;
        padding: 6px 16px;
        color: black;
        text-align: center;
    }
    #title {
        position: absolute;
        width: 500px;
        min-width: 300px;
        margin: 0 auto;
        left: 0; right: 0;
        top: 10px;
        padding: 6px;
        border-radius: 6px;
        background-color: white;
        opacity: 0.9;
    }
    #titleview {
        text-align: center;
    }
    #titleinput {
        width: 100%;
        border-width: 0;
        padding: 0;
        text-align: center;
    }
    #titleedit {
        border: 1px #444 solid;
        padding: 2px;
    }
    #editraw {
        position: absolute;
        top: 0px;
        bottom: 0px;
        left: 0px;
        right: 0px;
        background-color: black;
        opacity: 0.5;
        z-index: 1001;
        display: none;
    }
    #editrawbox, #historybox {
        width: 70%;
        height: 50%;
        margin: auto;
        position: absolute;
        top: 0px;
        bottom: 0px;
        left: 0px;
        right: 0px;
        opacity: 1.0;
        background-color: white;
        z-index: 1002;
        display: none;
    }
    #editrawta {
        border: none;
        padding: 0;
        resize: none;
        width: 100%;
        height: 100%;
    }
    #editrawtad {
        position: absolute;
        left: 6px;
        top: 6px;
        right: 6px;
        bottom: 30px;
        border: 1px solid black;
        padding: 4px;
    }
    #editrawbottom {
        position: absolute;
        left: 6px;
        right: 6px;
        bottom: 0px;
        text-align: left;
        line-height: 30px;
    }
    #fm {
        position: absolute;
        visibility: hidden;
        width: 0;
        height: 0;
        left: 0;
        top: 0;
    }
    #historybox {
        width: 500px;
    }
    #historybox .buttons {
        text-align: center;
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        line-height: 30px;
    }
    #historybox h2 {
        font-size: 12pt;
        text-align: center;
        margin: 0;
        line-height: 30px;
    }
    #histlistcontainter {
    }
    #historylist {
        overflow-y: auto;
        position: absolute;
        top: 30px;
        bottom: 30px;
        left: 0;
        right: 0;
    }
    .history-entry {
        /*border-top: 1px solid #ddd;
        padding-top: 4px;*/
        margin: 0 8px 8px;
        text-align: left;
    }
    .history-entry .date {
        font-size: 10pt;
        color: #666;
    }
    .history-entry .edit {
        float: right;
        font-size: 10pt;
    }
    .history-entry .title {
        clear: both;
    }
    .history-entry .title a {
        color: black;
    }
</style>
</head>
<body>
<div id="mapedit"></div>
<?php if( strlen(trim($message)) > 0 ) { ?><div id="message"><?=$message ?></div><?php } ?>

<div id="title" style="display: <?=$editing || strlen(trim($title)) > 0 ? 'block' : 'none' ?>;">
<div id="titleview" style="display: <?=$editing ? 'none' : 'block' ?>;"><?=htmlspecialchars($title) ?></div>
<div id="titleedit" style="display: <?=$editing ? 'block' : 'none' ?>;"><input type="text" maxlength="240" id="titleinput" placeholder="Map Title" value="<?=htmlspecialchars($title) ?>"></div>
</div>

<div id="editraw"></div>

<div id="editrawbox">
<div id="editrawtad">
    <textarea id="editrawta"></textarea>
</div>
<div id="editrawbottom">
    <input type="button" id="editrawbtn" value="Apply">
    <input type="button" id="editrawcancel" value="Cancel">
    <a href="http://mapbbcode.org" target="mapbbspec">What is MapBBCode?</a> |
    <a href="https://github.com/MapBBCode/mapbbcode/blob/master/BBCODE.md" target="mapbbspec">BBCode Specification</a>
</div>
</div>

<div id="historybox">
<h2>Your Code Library</h2>
<div id="histlistcontainer">
    <div id="historylist">
    <div class="history-entry">
    $$<div class="edit"><a href="/{codeid}/{editid}">edit</a></div>$$
    <div class="date">{updated}</div>
    <div class="title"><a href="/{codeid}" target="mapnew">{title}</a></div>
    </div>
    </div>
</div>
<div class="buttons">
    <input type="button" id="historycancel" value="Close">
    <input type="button" id="historyadd" value="Add current map">
    <input type="button" id="signout" value="Sign out">
</div>
</div>

<form action="/" method="post" id="fm" enctype="multipart/form-data">
    <input type="hidden" name="title" value=""/>
    <input type="hidden" name="bbcode" value=""/>
    <input type="hidden" name="codeid" value="<?=isset($scodeid) ? $scodeid : '' ?>"/>
    <input type="hidden" name="editid" value="<?=isset($seditid) ? $seditid : '' ?>"/>
    <input type="file" name="file">
</form>

<script>
if( document.getElementById('message') && document.getElementById('message').innerHTML.length > 0 && <?=isset($nohide) ? 'false' : 'true'?> ) {
    setTimeout(function() { document.getElementById('message').style.display = 'none'; }, 10000);
}

var bbcode = '<?=screen_param($bbcode) ?>';
createHistoryBox();

var mapBB = new MapBBCode({
    defaultPosition: [59.95, 30.3],
    defaultZoom: 7,
    maxInitialZoom: 16,
    editorHeight: 0,
    fullViewHeight: 0,
    fullFromStart: true,
    editorCloseButtons: false,
    preferStandardLayerSwitcher: false,
    leafletOptions: { scrollWheelZoom: true },
    createLayers: function(L) { return [
        L.tileLayer('http://129.206.74.245:8001/tms_r.ashx?x={x}&y={y}&z={z}', { name: 'OpenMapSurfer', attribution: 'Map &copy; <a href=\"http://openstreetmap.org\">OSM</a> | Tiles &copy; <a href=\"http://giscience.uni-hd.de/\">GIScience Heidelberg</a>', minZoom: 0, maxZoom: 19 }),
        'OSM',
        new L.BingLayer('Arzdiw4nlOJzRwOz__qailc8NiR31Tt51dN2D7cm57NrnceZnCpgOkmJhNpGoppU', { name: 'Bing Satellite' })
    ]}
});

<?php if( $editing ): ?>
openEditor(bbcode);
<?php else: ?>
var show = mapBB.show('mapedit', bbcode);

if( typeof editid === 'string' ) {
    var editBtn = L.functionButton('Edit', { position: 'topleft' });
    editBtn.on('clicked', function() {
        window.location = '/<?=$scodeid?>/' + editid;
    });
    show.map.addControl(editBtn);
}

var fork = L.functionButton('Fork', { position: 'topleft' });
fork.on('clicked', function() {
    show.close();
    document.getElementById('fm').elements['codeid'].value = '';
    openEditor(bbcode);
});
show.map.addControl(fork);

var bnew = L.functionButton('Create New', { position: 'topleft' });
bnew.on('clicked', function() {
    show.close();
    document.getElementById('titleinput').value = '';
    openEditor('');
});
show.map.addControl(bnew);

addImportExport(show);
addLogin(show);
<?php endif; ?>

function openEditor( bbcode ) {
    document.getElementById('titleview').style.display = 'none';
    document.getElementById('titleedit').style.display = 'block';
    document.getElementById('title').style.display = 'block';
    var edit = mapBB.editor('mapedit', bbcode);
    var save = L.functionButton('Save', { position: 'topleft' });
    save.on('clicked', function() {
        submit('save', edit);
    });
    edit.map.addControl(save);
    var editbb = L.functionButton('Edit Raw', { position: 'topleft' });
    editbb.on('clicked', function() {
        openCodeEditor(edit);
    });
    edit.map.addControl(editbb);

    var imprt = L.functionButton('Import', { position: 'topleft' });
    imprt.on('clicked', function() {
        var field = document.getElementById('fm').elements['file'];
        field.onchange = function() {
            submit('import', edit);
        }
        field.click();
    });
    edit.map.addControl(imprt);

    addImportExport(edit);
    addLogin(edit);
}

function addImportExport(ui) {
    // import only in edit mode now
    var exprt = L.exportControl({
        types:  '<?=implode(',', $fmtdesc['types']) ?>'.split(','),
        titles: '<?=implode(',', $fmtdesc['titles']) ?>'.split(',')
    });
    exprt.on('export', function(e) {
        if( e.fmt )
            submit('export/' + e.fmt, ui);
    });
    ui.map.addControl(exprt);
}

function addLogin(ui) {
    var loggedIn = <?=isset($userid) ? 'true' : 'false' ?>;
    var login = L.functionButton(loggedIn ? 'Library' : 'Sign In', { position: 'topright' });
    login.on('clicked', function() {
        if( loggedIn ) {
            showHistoryWindow(true);
        } else {
            window.storedBBCode = ui.getBBCode();
            window.open('/auth.php', 'mapbbauth', 'dialog,resizable,width=700,height=400');
        }
    });
    ui.map.addControl(login);
    document.getElementById('historyadd').onclick = function() {
        submit('bookmark', ui);
    };
    document.getElementById('signout').onclick = function() {
        submit('signout', ui);
    };
}

function submit( action, edit ) {
    var bbcode = edit ? edit.getBBCode() : window.storedBBCode;
    if( !bbcode ) return;
    var form = document.getElementById('fm');
    form.action = '/' + (action || '');
    form.elements['title'].value = document.getElementById('titleinput').value;
    form.elements['bbcode'].value = bbcode;
    form.submit();
}

function createHistoryBox() {
    var library = <?=isset($userid) && isset($library) ? json_encode($library) : 'false' ?>;
    var viewcodeid = <?=!$editing && isset($scodeid) ? "'$scodeid'" : 'false' ?>;
    if( !library ) return;
    var box = document.getElementById('historylist'), i;
        template = box.innerHTML, result = '';
    while( box.firstChild )
        box.removeChild(box.firstChild);
    for( i = 0; i < library.length; i++ ) {
        var str = template.replace('{title}', library[i].title || library[i].codeid);
        str = str.replace(/{codeid}/g, library[i].codeid);
        str = str.replace('{editid}', library[i].editid);
        str = str.replace('{created}', library[i].created);
        str = str.replace('{updated}', library[i].updated);
        if( library[i].editid )
            str = str.replace(/\$\$/g, '');
        else
            str = str.replace(/\$\$.+\$\$/, '');
        result += str;
        if( library[i].codeid === '<?=isset($scodeid) ? $scodeid : '-1' ?>' && library[i].editid )
            editid = library[i].editid; // set global variable
        if( viewcodeid === library[i].codeid )
            viewcodeid = false;
    }
    box.innerHTML = result;
    document.getElementById('historyadd').style.display = viewcodeid ? 'inline' : 'none';
}

function showHistoryWindow(show) {
    document.getElementById('editraw').style.display = show ? 'block' : 'none';
    document.getElementById('historybox').style.display = show ? 'block' : 'none';
    document.getElementById('editraw').onclick = document.getElementById('historycancel').onclick = !show ? null : function() {
        showHistoryWindow(false);
    }
}

function showEditRaw(show) {
    document.getElementById('editraw').style.display = show ? 'block' : 'none';
    document.getElementById('editrawbox').style.display = show ? 'block' : 'none';
    document.getElementById('editraw').onclick = document.getElementById('editrawcancel').onclick = !show ? null : function() {
        showEditRaw(false);
    }
}

function openCodeEditor(edit) {
    document.getElementById('editrawta').value = edit.getBBCode();
    showEditRaw(true);
    document.getElementById('editrawbtn').onclick = function() {
        edit.updateBBCode(document.getElementById('editrawta').value);
        document.getElementById('editrawbtn').onclick = null;
        showEditRaw(false);
    }
}

</script>

<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(["setDomains", ["*.mapbbcode.org","*.share.mapbbcode.org"]]);
  _paq.push(["trackPageView"]);
  _paq.push(["enableLinkTracking"]);

  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://piwik.textual.ru/";
    _paq.push(["setTrackerUrl", u+"piwik.php"]);
    _paq.push(["setSiteId", "6"]);
    var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
    g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Piwik Code -->
</body>
</html>
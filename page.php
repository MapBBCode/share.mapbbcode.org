<!DOCTYPE html>
<html>
<head>
<title><?=isset($title) && strlen($title) > 0 ? htmlspecialchars($title).' — ' : (isset($scodeid) && strlen($scodeid) > 0 ? $scodeid.' — ' : '') ?>MapBBCode Share</title>
<meta charset="utf-8" />
<?php if( isset($seditid) ) { ?><meta name="robots" content="noindex, nofollow"><?php } ?>
<?php $lib = $doc_path.'/lib'; ?>
<link rel="stylesheet" href="<?=$lib ?>/leaflet.css" />
<link rel="stylesheet" href="<?=$lib ?>/leaflet.draw.css" />
<script src="<?=$lib ?>/leaflet.js"></script>
<script src="<?=$lib ?>/leaflet.draw.js"></script>
<script src="<?=$lib ?>/mapbbcode.js"></script>
<script src="<?=$lib ?>/Bing.js"></script>
<script src="<?=$lib ?>/Handler.Simplify.js"></script>
<script src="<?=$lib ?>/Handler.Length.js"></script>
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
        text-align: left;
        line-height: 1.5;
        border: none;
        z-index: 3000;
    }
    #message a {
        color: blue;
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
        z-index: 1000;
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
    <a href="http://mapbbcode.org/bbcode.html" target="mapbbspec">BBCode Specification</a>
</div>
</div>

<div id="historybox">
<h2>Your Code Library</h2>
<div id="histlistcontainer">
    <div id="historylist">
    <div class="history-entry">
    $$<div class="edit"><a href="<?=$base_path ?>/{codeid}/{editid}">edit</a></div>$$
    <div class="date">{updated}</div>
    <div class="title"><a href="<?=$base_path ?>/{codeid}" target="mapnew">{title}</a></div>
    </div>
    </div>
</div>
<div class="buttons">
    <input type="button" id="historycancel" value="Close">
    <input type="button" id="historyadd" value="Add current map">
    <input type="button" id="signout" value="Sign out">
</div>
</div>

<form action="<?=$base_path ?>" method="post" id="fm" enctype="multipart/form-data">
    <input type="hidden" name="title" value=""/>
    <input type="hidden" name="bbcode" value=""/>
    <input type="hidden" name="format" value=""/>
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
<?php if( defined('DEFAULT_LAT') ) { echo "\tdefaultPosition: [".DEFAULT_LAT.', '.DEFAULT_LNG."],\n\tdefaultZoom: ".DEFAULT_ZOOM.",\n"; } ?>
    maxInitialZoom: 16,
    editorHeight: 0,
    fullViewHeight: 0,
    fullFromStart: true,
    editorCloseButtons: false,
    preferStandardLayerSwitcher: false,
    measureButton: true,
<?php if( isset($TILE_LAYERS) ): ?>
    createLayers: function(L) { return [
        <?= implode(",\n\t", $TILE_LAYERS)."\n" ?>
    ]},
<?php endif ?>
    leafletOptions: { scrollWheelZoom: true, minZoom: 3, maxZoom: 18, attributionEditLink: true }
});
mapBB.setStrings({ helpContents: 
<?php
    $helpContents = file_get_contents('help.txt');
    $helpContents = str_replace('{version}', VERSION, $helpContents);
    $helpContents = str_replace("\r", '', $helpContents);
    echo "'".str_replace("'", "\\'", str_replace("\n", "\\n", $helpContents))."'";
?>
});

<?php if( $editing ): ?>
openEditor(bbcode);
<?php else: ?>
var show = mapBB.show('mapedit', bbcode);

if( typeof editid === 'string' ) {
    var editBtn = L.functionButtons([{ content: 'Edit' }], { position: 'topleft' });
    editBtn.on('clicked', function() {
        window.location = '<?=$base_path ?>/<?=$scodeid?>/' + editid;
    });
    show.map.addControl(editBtn);
}

var fork = L.functionButtons([{ content: 'Fork' }], { position: 'topleft' });
fork.on('clicked', function() {
    show.close();
    document.getElementById('fm').elements['codeid'].value = '';
    openEditor(bbcode);
});
show.map.addControl(fork);

var bnew = L.functionButtons([{ content: 'Create New' }], { position: 'topleft' });
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
    var modifyListener = {
        saveButtonStyle: false,
        modified: function() {
            if( this.saveButtonStyle ) {
                this.saveButtonStyle.backgroundColor = '#fee';
                this.saveButtonStyle.fontWeight = 'bold';
            }
        },

        reKeys: new RegExp('a^'),
        applicableTo: function() { return true; },
        objectToLayer: function() {},
        layerToObject: function() {},

        // install change listener
        createEditorPanel: function( layer ) {
            layer.on('edit remove', this.modified, this);
        }
    };
    window.mapBBCodeHandlers.push(modifyListener);

    document.getElementById('titleview').style.display = 'none';
    document.getElementById('titleedit').style.display = 'block';
    document.getElementById('title').style.display = 'block';
    var edit = mapBB.editor('mapedit', bbcode);
    var save = L.functionButtons([{ content: '<span style="font-size: 12pt;">Save</span>' }], { position: 'topleft' });
    save.on('clicked', function() {
        submit('save', edit);
    });
    edit.map.addControl(save);
    modifyListener.saveButtonStyle = save._buttons[0].link.style;
    edit.map.on('draw:created', modifyListener.modified, modifyListener);
    var editbb = L.functionButtons([{ content: 'Edit Raw' }], { position: 'topleft' });
    editbb.on('clicked', function() {
        openCodeEditor(edit);
    });
    edit.map.addControl(editbb);

    var imprt = L.functionButtons([{ content: 'Import' }], { position: 'topleft' });
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
            submit('export', ui, e.fmt);
    });
    ui.map.addControl(exprt);
}

function addLogin(ui) {
    <?php if( !db_available() ) { ?>return;<?php } ?>
    var loggedIn = <?=isset($userid) ? 'true' : 'false' ?>;
    var login = L.functionButtons([{ content: loggedIn ? 'Library' : 'Sign In' }], { position: 'topright' });
    login.on('clicked', function() {
        if( loggedIn ) {
            showHistoryWindow(true);
        } else {
            window.storedBBCode = ui.getBBCode();
            window.open('<?php echo $base_path ?>/auth.php', 'mapbbauth', 'dialog,resizable,width=700,height=400');
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

function submit( action, edit, format ) {
    var bbcode = edit ? edit.getBBCode() : window.storedBBCode;
    if( !bbcode ) return; // todo: is it needed? submit() from view does nothing
    var form = document.getElementById('fm');
    form.action = '<?=$base_path ?>/' + (action || '');
    form.elements['title'].value = document.getElementById('titleinput').value;
    form.elements['bbcode'].value = bbcode;
    form.elements['format'].value = format || '';
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
<?php @include('footer.php'); ?>
</body>
</html>

/*
	Simple OpenID Plugin
	http://code.google.com/p/openid-selector/
	
	This code is licensed under the New BSD License.
*/

var providers;

var openid = {
	version : '1.3', // version constant
	demo : false,
	demo_text : null,

	img_path : 'lib/images/',
	locale : null, // is set in openid-<locale>.js
	sprite : null, // usually equals to locale, is set in
	// openid-<locale>.js
	signin_text : null, // text on submit button on the form
	all_small : false, // output large providers w/ small icons
	no_sprite : false, // don't use sprite image
	image_title : '{provider}', // for image title

	input_id : null,
	provider_url : null,
	provider_id : null,

	/**
	 * Class constructor
	 * 
	 * @return {Void}
	 */
	init : function(input_id) {
		providers = new Object();
		for (var attrname in providers_large)
		{
			providers[attrname] = providers_large[attrname];
		}
		for (var attrname in providers_small)
		{
			providers[attrname] = providers_small[attrname];
		}

		var openid_btns = document.getElementById('openid_btns');
		this.input_id = input_id;
		document.getElementById('openid_choice').style.display = 'block';
		var toEmpty = document.getElementById('openid_input_area');
		while (typeof(toEmpty.firstChild) != "undefined" && toEmpty.firstChild)
		{
			toEmpty.removeChild(toEmpty.firstChild);
		}
		var i = 0;
		// add box for each provider
		var id, box;
		for (id in providers_large) {
			box = this.getBoxHTML(id, providers_large[id], (this.all_small ? 'small' : 'large'), i++);
			openid_btns.appendChild(box);
		}
		if (providers_small) {
			openid_btns.appendChild(document.createElement('br'));
			for (id in providers_small) {
				box = this.getBoxHTML(id, providers_small[id], 'small', i++);
				openid_btns.appendChild(box);
			}
		}
		document.getElementById('openid_form').onsubmit = this.submit;
	},

	/**
	 * @return {Element}
	 */
	getBoxHTML : function(box_id, provider, box_size, index) {
		if (this.no_sprite) {
			var image_ext = box_size == 'small' ? '.ico.gif' : '.gif';
			var toReturn = document.createElement("a");
			toReturn.href = "javascript:openid.signin('" + box_id + "');";
			toReturn.title = this.image_title.replace('{provider}', provider["name"]);
			toReturn.className = box_id + ' openid_' + box_size + '_btn';
			toReturn.style.display = 'block';
			toReturn.style.background = '#FFF url(' + this.img_path + '../images.' + box_size + '/' + box_id + image_ext + ') no-repeat center center';
			return toReturn;
		}
		var x = box_size == 'small' ? -index * 24 : -index * 100;
		var y = box_size == 'small' ? -60 : 0;
		var toReturn = document.createElement("a");
		toReturn.href = "javascript:openid.signin('" + box_id + "');";
		toReturn.title = this.image_title.replace('{provider}', provider["name"]);
		toReturn.className = box_id + ' openid_' + box_size + '_btn';
		toReturn.style.background = '#FFF url(' + this.img_path + 'openid-providers-' + this.sprite + '.png'	+ ') no-repeat center center';
		toReturn.style.backgroundPosition = x + 'px ' + y + 'px';
		return toReturn;
	},

	/**
	 * Provider image click
	 * 
	 * @return {Void}
	 */
	signin : function(box_id, onload) {
		var provider = providers[box_id];
		if (!provider) {
			return;
		}
		this.highlight(box_id);
		this.provider_id = box_id;
		this.provider_url = provider.url;
		// prompt user for input?
		if (provider.label) {
			this.useInputBox(provider);
		} else {
			var toEmpty = document.getElementById('openid_input_area');
			while (typeof(toEmpty.firstChild) != "undefined" && toEmpty.firstChild)
			{
				toEmpty.removeChild(toEmpty.firstChild);
			}
			if (!onload) {
				document.getElementById('openid_form_submit').click(); //document.getElementById('openid_form').submit();
			}
		}
	},

	/**
	 * Sign-in button click
	 * 
	 * @return {Boolean}
	 */
	submit : function() {
		var url = openid.provider_url;
		if (url) {
			var oiduser = document.getElementById('openid_username');
			if (oiduser)
				url = url.replace('{username}', oiduser.value);
			openid.setOpenIdUrl(url);
		}
		if (openid.demo) {
			alert(openid.demo_text + "\r\n" + document.getElementById(openid.input_id).value);
			return false;
		}
		if (url && url.indexOf("javascript:") == 0) {
			url = url.substr("javascript:".length);
			eval(url);
			return false;
		}
		return true;
	},

	/**
	 * @return {Void}
	 */
	setOpenIdUrl : function(url) {
		var hidden = document.getElementById(this.input_id);
		if (hidden) {
			hidden.value = url;
		} else {
			var toAdd = document.createElement('input');
			toAdd.type = 'hidden';
			toAdd.id = this.input_id;
			toAdd.name = this.input_id;
			toAdd.value = url;
			document.getElementById('openid_form').appendChild(toAdd);
		}
	},

	/**
	 * @return {Void}
	 */
	highlight : function(box_id) {
		// remove previous highlight.
		var highlight = document.getElementById('openid_highlight');
		if (highlight) {
			for (var myNode in highlight.childNodes)
			{
				if (highlight.childNodes[myNode].nodeName == 'A' || highlight.childNodes[myNode].nodeName == 'a')
				{
				  highlight.parentNode.replaceChild(highlight.childNodes[myNode], highlight);
					break;
				}
			}
		}
		// add new highlight.
		var toAdd = document.createElement('div');
		toAdd.id = 'openid_highlight';
		var aElements = document.getElementsByTagName('a');
		for (var myA in aElements)
		{
			var el = aElements[myA];
			if (el.className == box_id ||
					el.className.indexOf(" " + box_id + " ") >= 0 ||
					el.className.indexOf(box_id + " ") == 0 ||
					el.className.indexOf(" " + box_id) == el.className.length - (box_id.length + 1))
			{
				var temp = document.createElement('b');
				var parent = el.parentNode;
				parent.replaceChild(temp, el);
				toAdd.appendChild(el);
				parent.replaceChild(toAdd, temp);
				break;
			}
		}
	},

	/**
	 * @return {Void}
	 */
	useInputBox : function(provider) {
		var input_area = document.getElementById('openid_input_area');
		var html = '';
		var id = 'openid_username';
		var value = '';
		var label = provider.label;
		var style = '';
		if (label) {
			html = '<p>' + label + '</p>';
		}
		if (provider.name == 'OpenID') {
			id = this.input_id;
			value = 'http://';
			style = 'background: #FFF url(' + this.img_path + 'openid-inputicon.gif) no-repeat scroll 0 50%; padding-left:18px;';
		}
		html += '<input id="' + id + '" type="text" style="' + style + '" name="' + id + '" value="' + value + '" />'
				+ '<input id="openid_submit" type="submit" value="' + this.signin_text + '"/>';
		while (typeof(input_area.firstChild) != "undefined" && input_area.firstChild)
		{
			input_area.removeChild(input_area.firstChild);
		}
		input_area.innerHTML = html;
		document.getElementById(id).focus();
	},

	setDemoMode : function(demoMode) {
		this.demo = demoMode;
	}
};

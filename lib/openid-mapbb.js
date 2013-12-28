/*
	Simple OpenID Plugin
	http://code.google.com/p/openid-selector/
	
	This code is licensed under the New BSD License.
*/

var providers_large = {
	google : {
		name : 'Google',
		url : 'https://www.google.com/accounts/o8/id'
	},
	yahoo : {
		name : 'Yahoo',
		url : 'http://me.yahoo.com/'
	},
	yandex : {
		name : 'Яндекс',
		url : 'http://openid.yandex.ru'
	},
	vkontakte : {
		name : 'VKontakte',
		url : 'http://vkontakteid.ru'
	},
	openid : {
		name : 'OpenID',
		label : 'Enter your OpenID.',
		url : null
	}
};

var providers_small = {
	livejournal : {
		name : 'LiveJournal',
		label : 'Enter your Livejournal username.',
		url : 'http://{username}.livejournal.com/'
	},
	wordpress : {
		name : 'Wordpress',
		label : 'Enter your Wordpress.com username.',
		url : 'http://{username}.wordpress.com/'
	},
	blogger : {
		name : 'Blogger',
		label : 'Your Blogger account',
		url : 'http://{username}.blogspot.com/'
	},
	aol : {
		name : 'AOL',
		label : 'Enter your AOL screenname.',
		url : 'http://openid.aol.com/{username}'
	},
	verisign : {
		name : 'Verisign',
		label : 'Your Verisign username',
		url : 'http://{username}.pip.verisignlabs.com/'
	},
	claimid : {
		name : 'ClaimID',
		label : 'Your ClaimID username',
		url : 'http://claimid.com/{username}'
	},
	clickpass : {
		name : 'ClickPass',
		label : 'Enter your ClickPass username',
		url : 'http://clickpass.com/public/{username}'
	},
	myopenid : {
		name : 'MyOpenID',
		label : 'Enter your MyOpenID username.',
		url : 'http://{username}.myopenid.com/'
	}
};

openid.locale = 'en';
openid.sprite = 'mapbb';
openid.demo_text = 'In client demo mode. Normally would have submitted OpenID:';
openid.signin_text = 'Sign-In';
openid.image_title = 'log in with {provider}';

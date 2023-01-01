/* ------------------------------------------------------------------------ *\
    Klasa wielokrotnego użytku do "łagodnych" komunikatów Javascriptowych
	
    Copyright:  ©2008-2010 Maciej Jaros (pl:User:Nux, en:User:Nux)
     Licencja:  GNU General Public License v2
                http://opensource.org/licenses/gpl-license.php

	Note:
		For best results in IE add this to your CSS:
		html {height:100%}
\* ------------------------------------------------------------------------ */

function sftJSmsg()
{
	/* ------------------------------------------------------------------------ *\
		Basic attributes of sftJSmsg
	\* ------------------------------------------------------------------------ */
	//
	// VERSION
	this.ver = this.version = '0.3.2';
	
	this.msgEls = new Array();

	// settings
	this.showCancel = false;	// show cancel button
	this.noButtons = false;		// no buttons - NOTE: you'll have to close message for yourself if you use it
	this.createRegularForm = false;	// instead of a simple popup use popup to submit a form created in it
	this.RegularForm = {	// settings for a form (if createRegularForm==ture)
		'method' : 'POST',			// default method
		'action' : location.href	// default actions
	};
	this.autoOKClose = true;	// add close action for OK button
	
	this.styleZbase = 40000;	// base z-index for msg (note that there can be more z-indexes used)
	this.styleTop = 100;
	this.styleWidth = 330;
	this.pozFromTop = 40;
	
	// lang
	this.lang = {
		'OK' : 'OK',
		'Cancel' : 'Anuluj'
	};

	// elements on this list will be hidden when the message is displayed
	this.elsToHideWhenShown = [
		'object',
		'embed',
		'iframe'
	];

	// inner
	this.isVisible = false;

	/* ------------------------------------------------------------------------ *\
		Methods of sftJSmsg that might be directly hooked to a button
	\* ------------------------------------------------------------------------ */
	var _this = this;	// to avoid this problems when hooked to a button
	//
	// .close()
	//
	this.close = function()
	{
		for (var i=0; i<_this.msgEls.length; i++)
		{
			_this.msgEls[i].style.display = 'none';
		}

		// show flash and similar (only thoose that were previously hidden)
		_this.flashlikeShow();
		_this.isVisible = false;
	}
}

/* ------------------------------------------------------------------------ *\
    Methods of sftJSmsg
\* ------------------------------------------------------------------------ */
//
// Init to be run once unload
//
sftJSmsg.prototype.init = function()
{
	var nel;
	
	//
	// document shade
	this.addShadeElement();

	//
	// main message element
	this.elMsgContainer = this.addMsgContainer();

	//
	// form element
	if (this.createRegularForm)
	{
		// when the form is created all other elements should be added to the form
		this.elMsgContainer = addRegularForm(this.elMsgContainer, this.RegularForm);

	}

	//
	// message content...
	this.addMsgContentEl();
	// ...and std buttons
	if (!this.noButtons)
	{
		this.addStdButtons();
	}
	
	// setup user changeable styles
	this.reInit();
	
	// enable resize
	window.sftJSmsgs[window.sftJSmsgs.length] = this;
}

//
// Add message content element (you might call it a content container)
//
sftJSmsg.prototype.addMsgContentEl = function()
{
	var nel;
	nel = document.createElement('div');
	nel.style.margin = '1em .5em';
	this.elMsgContainer.appendChild(nel);
	this.elContent = nel;
}

//
// Add main message element (container)
//
sftJSmsg.prototype.addRegularForm = function(elMsgContainer, arrFormAttrs)
{
	var nel;
	nel = document.createElement('form');
	for (var key in arrFormAttrs)
	{
		nel.setAttribute(key, arrFormAttrs[key]);
	}
	elMsgContainer.appendChild(nel);
	return nel;
}

//
// Add main message element (container)
//
sftJSmsg.prototype.addMsgContainer = function()
{
	var glob = document.body;
	var nel;
	nel = document.createElement('div');
	nel.style.cssText = 'text-align:center;background:white;padding:5px 10px;border:1px solid #CCC;position:absolute;';
	// min-height
	// if (nel.style.maxHeight==undefined)	nel.style.height='300px'; // IE blah...
	nel.style.display = 'none';
	glob.appendChild(nel);
	this.elMain = this.msgEls[this.msgEls.length] = nel;
	return this.elMain;
}

//
// Add shade of the page area
//
sftJSmsg.prototype.addShadeElement = function()
{
	var glob = document.body;
	var nel;
	nel = document.createElement('div');
	nel.style.cssText = 'background:white;filter:alpha(opacity=75);opacity:0.75;position:absolute;left:0px;top:0px;';
	nel.style.width = document.documentElement.scrollWidth+'px';
	nel.style.height= document.documentElement.scrollHeight+'px';
	nel.style.display = 'none';
	glob.appendChild(nel);
	this.msgEls[this.msgEls.length] = nel;
}

//
// Add a button
//
// this.addMsgButton ({name, value[, type][, style]})
// if type not given "button is assumed"
sftJSmsg.prototype.addMsgButton = function(oBtnParams)
{
	var nel;
	
	//
	// message buttons container
	if (typeof(this.msgBtns)!='object')
	{
		nel = document.createElement('div');
		nel.style.marginBottom = '1em';
		this.elMsgContainer.appendChild(nel);
		this.msgBtns = new Object();
		this.msgBtns.parent = nel;
		this.msgBtns.parent.sftJSmsg = this;	// backref
	}
	
	//
	// the button
	nel = document.createElement('input');
	nel.setAttribute('type', (typeof(oBtnParams.type)!='string' ? 'button' : oBtnParams.type));
	nel.setAttribute('name', oBtnParams.name);
	nel.setAttribute('value', oBtnParams.value);
	if (typeof(oBtnParams.style)=='string')
	{
		nel.style.cssText = oBtnParams.style;
	}
	this.msgBtns.parent.appendChild(nel);
	return nel;
}

//
// Add a form field to content
//
// this.addMsgButton ({name, value[, type][, style]})
// if type not given "button is assumed"
sftJSmsg.prototype.addMsgButton = function(oBtnParams)
{
	var nel;
	
	//
	// message buttons container
	if (typeof(this.msgBtns)!='object')
	{
		nel = document.createElement('div');
		nel.style.marginBottom = '1em';
		this.elMsgContainer.appendChild(nel);
		this.msgBtns = new Object();
		this.msgBtns.parent = nel;
		this.msgBtns.parent.sftJSmsg = this;	// backref
	}
	
	//
	// the button
	nel = document.createElement('input');
	nel.setAttribute('type', (typeof(oBtnParams.type)!='string' ? 'button' : oBtnParams.type));
	nel.setAttribute('name', oBtnParams.name);
	nel.setAttribute('value', oBtnParams.value);
	if (typeof(oBtnParams.style)=='string')
	{
		nel.style.cssText = oBtnParams.style;
	}
	this.msgBtns.parent.appendChild(nel);
	return nel;
}

//
// Add standard button elements
//
sftJSmsg.prototype.addStdButtons = function()
{
	// OK (always)
	var elOK = this.addMsgButton ({
		name : 'submit',
		value: this.lang['OK'],
		type : (!this.createRegularForm ? 'button' : 'submit'),
		style: 'padding:0 1em'
	});
	this.msgBtns.ok = elOK;
	
	// Cancel (if asked for by the user)
	if (this.showCancel)
	{
		elCancel = this.addMsgButton ({
			name : 'submit',
			value: this.lang['Cancel'],
			style: 'margin-left:1em'
		});
		elCancel.onclick = this.close;
		this.msgBtns.cancel = elCancel;
	}
}

//
// Reposition the message box to the center of the screen (note this is different then center of the document)
//
sftJSmsg.prototype.repositionMsgCenter = function()
{
	var intHeight = 200;	// default
	if (this.isVisible && this.elMsgContainer.scrollHeight>intHeight)
	{
		intHeight = this.elMsgContainer.scrollHeight; // * 0.9;	// + some extra
	}

	this.styleTop = undefined;	// = auto-top = don't scroll
	var win_size = qmGetWindowSize();
	//intPozTop = Math.floor(win_size[1]/2)-100;	// ~middle
	var intPozTop = Math.floor((win_size[1]-intHeight)/2);	// ~middle
	if (intPozTop<0)
	{
		intPozTop = 20;
	}
	this.pozFromTop = intPozTop;
	
	// if already visible then we need to reinit to apply position changes
	if (this.isVisible)
	{
		this.reInit();
	}
}

//
// .reInit()
//
// reInit so that changed styles will work
sftJSmsg.prototype.reInit = function()
{
	//
	// ew. korekta wielkości cienia z tyłu
	var shade_el = this.msgEls[0];
	shade_el.style.width = document.documentElement.scrollWidth+'px';
	shade_el.style.height= document.documentElement.scrollHeight+'px';

	//
	// z-index
	for (var i=0; i<this.msgEls.length; i++)
	{
		this.msgEls[i].style.zIndex = this.styleZbase+i;
	}
	
	//
	// top
	if (this.styleTop==undefined)	// auto-top
	{
		var cur_scroll = qmGetPageScroll();
		this.styleTop = cur_scroll[1]+this.pozFromTop;
	}
	this.elMain.style.top = this.styleTop+'px';

	//
	// width + left
	var left=undefined;
	if (this.styleWidth==undefined)	// auto-width
	{
		this.elMain.style.width = '';
		if (this.styleLeft==undefined)
		{
			left = 100;	// if both undefined then left cannot be computed
		}
	}
	else
	{
		this.elMain.style.width = this.styleWidth+'px';
	}
	// final left setup
	var glob = document.body;
	if (left==undefined && this.styleLeft==undefined)	// if not yet set
	{
		left = Math.floor(glob.clientWidth/2 - this.styleWidth/2);
	}
	else
	{
		left = this.styleLeft;
	}
	this.elMain.style.left	= ((left<10) ? 10 : left)+'px';	// including padding
}

//
// .show(html, strOKclick)
//
sftJSmsg.prototype.show = function(html, strOKclick)
{
	var wasVisible = this.isVisible;	// needed for flash hidding...
	this.isVisible = true;

	// init / reInit
	if (this.msgEls.length==0)
	{
		this.init();
	}
	else
	{
		this.reInit();
	}
	
	//
	// message
	if (!this.prevHTML || html!=this.prevHTML)
	{
		this.prevHTML = html;
		this.elContent.innerHTML = html;
	}
	
	if (!this.noButtons)
	{
		//
		// action
		if (typeof strOKclick =='string' && strOKclick.length>0)
		{
			if (this.autoOKClose)
			{
				this.msgBtns.ok.akcja = this.close;
				this.msgBtns.ok.onclick = new Function(strOKclick +'; this.akcja()');
			}
			else
			{
				this.msgBtns.ok.onclick = new Function(strOKclick);
			}
		}
		else //if (!this.createRegularForm)
		{
			this.msgBtns.ok.onclick = this.close;
		}
	}
	
	//
	// hide flash and similar which might be on top
	if (!wasVisible)
	{
		this.flashlikeHide();
	}
	
	//
	// show message
	for (var i=0; i<this.msgEls.length; i++)
	{
		this.msgEls[i].style.display = 'block';
	}
	
	// scroll
	var cur_scroll = qmGetPageScroll();
	window.scroll(cur_scroll[0], this.styleTop-this.pozFromTop);
}

//
// flashlikeHide/Show
//
var sftJSmsg_flashlikeHidden_count = 0;	// probably should be added to elements that are hidden but should work for now...
sftJSmsg.prototype.flashlikeHide = function()
{
	// check if already hidden
	sftJSmsg_flashlikeHidden_count++;
	if (sftJSmsg_flashlikeHidden_count>1)
	{
		return;
	}
	
	// go
	for (var j=0; j<this.elsToHideWhenShown.length; j++)
	{
		if (this.elsToHideWhenShown[j].length<1)
			continue;
		var fls = document.getElementsByTagName(this.elsToHideWhenShown[j]);
		for (var i=0; i<fls.length; i++)
		{
			if (fls[i].style.visibility != 'hidden')
			{
				fls[i].hidden_sftJSmsg_old_visibility = fls[i].style.visibility;
				fls[i].style.visibility = 'hidden';
			}
		}
	}
}
sftJSmsg.prototype.flashlikeShow = function()
{
	// check if still needs to be hidden
	sftJSmsg_flashlikeHidden_count--;
	if (sftJSmsg_flashlikeHidden_count>0)
	{
		return;
	}
	
	// go
	for (var j=0; j<this.elsToHideWhenShown.length; j++)
	{
		if (this.elsToHideWhenShown[j].length<1)
			continue;
		var fls = document.getElementsByTagName(this.elsToHideWhenShown[j]);
		for (var i=0; i<fls.length; i++)
		{
			if (typeof(fls[i].hidden_sftJSmsg_old_visibility)!='undefined')
			{
				fls[i].style.visibility = fls[i].hidden_sftJSmsg_old_visibility;
			}
		}
	}
}

//
// .setOKdisabled(disable)
//
sftJSmsg.prototype.setOKdisabled = function(disable)
{
	this.msgBtns.ok.disabled = disable;
}


/* ------------------------------------------------------------------------ *\
	Simple messages
	
	TODO
	* add a way to stop quee messages (in the second message comming from quee)
\* ------------------------------------------------------------------------ */
if (typeof(addOnloadHook) != 'function')
{
	function addOnloadHook(fun)
	{
		if (window.addEventListener)
		{
			window.addEventListener('load', fun, false);
		}
		else if (window.attachEvent)
		{
			window.attachEvent('onload', fun);
		}
		else
		{
			window.onload=fun;
		}
	}
}

//
// Object init
//
var oJsAlert = {
	oMsg : null,
	arrQuee : [],
	isVisible : false,
	isQueeNotEmpty : false
}

//
// init msg for alerts
//
oJsAlert.init = function ()
{
	var msg = new sftJSmsg();
	/*
	msg.styleTop = undefined;	// = auto-top = don't scroll
	var win_size = qmGetWindowSize();
	poz_top = Math.floor(win_size[1]/2)-100;	// ~middle
	if (poz_top<0)
	{
		poz_top = 20;
	}
	msg.pozFromTop = poz_top;
	*/
	msg.repositionMsgCenter();
	msg.styleWidth = 400;
	msg.showCancel = false;
	msg.autoOKClose = false;
	msg.createRegularForm = false;
	msg.styleZbase = 65000;	// should be above other standard messages
	
	oJsAlert.oMsg = msg;

	// quee
	if (oJsAlert.isQueeNotEmpty)
	{
		oJsAlert.quee();
	}
}
addOnloadHook (oJsAlert.init);

//
// Unqueed show
//
oJsAlert.show = function (txt)
{
	// set visibility for quee
	oJsAlert.isVisible = true;
	
	// add quee runner
	var strOKclick = "oJsAlert.close();";

	// show alert
	var msg = oJsAlert.oMsg;
	/*
	msg.styleTop = undefined;	// = auto-top = don't scroll
	var win_size = qmGetWindowSize();
	poz_top = Math.floor(win_size[1]/2)-200;	// ~middle
	if (poz_top<0)
	{
		poz_top = 20;
	}
	msg.pozFromTop = poz_top;
	*/
	msg.repositionMsgCenter();
	msg.styleWidth = 400;
	msg.show(''
		+'<div class="jsAlert">'
			+txt
		+'</div>'
		,strOKclick
	);
}

//
// hide and check for next
//
oJsAlert.close = function ()
{
	// set visibility
	oJsAlert.isVisible = false;
	
	// close (hide) message box
	oJsAlert.oMsg.close();
	
	// quee
	oJsAlert.quee();
}
// quee (FIFO)
oJsAlert.quee = function ()
{
	if (oJsAlert.arrQuee.length>0)
	{
		var txt = oJsAlert.arrQuee.shift();
		jsAlert(txt);
	}
	else
	{
		oJsAlert.isQueeNotEmpty = false;
	}
}

//
// alert() replacement
//
function jsAlert(txt)
{
	// not loaded? add to quee...
	if (oJsAlert.oMsg == null)
	{
		oJsAlert.arrQuee.push(txt);
		if (oJsAlert.arrQuee.length==1)
		{
			oJsAlert.isQueeNotEmpty = true;	// avoids IE problems with ordering calls
			//addOnloadHook (function(){oJsAlert.quee()});
		}
	}
	// already visible? add to quee
	else if (oJsAlert.isVisible)
	{
		oJsAlert.arrQuee.push(txt);
	}
	// otherwise just show
	else
	{
		oJsAlert.show (txt);
	}
}

/* ------------------------------------------------------------------------ *\
	Various functions based on info and scripts from
	www.quirksmode.org
	
    Copyright:  ©2008 Maciej Jaros (pl:User:Nux, en:User:Nux), Peter-Paul Koch
      License:  Public domain
\* ------------------------------------------------------------------------ */
// element [left, top]
function qmFindPos(obj)
{
	if (typeof obj != 'object' || obj==null)
	{
		return [0,0];
	}
	
	var curleft = curtop = 0;
	if (obj.offsetParent)
	{
		do
		{
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
		}
		while (obj = obj.offsetParent);
	}
	return [curleft, curtop];
}
// page X, Y scroll [width, height]
function qmGetPageScroll()
{
	var retArray;

	if (self.pageYOffset)	// FF, Opera (probably all except IE)
	{
		retArray = [self.pageXOffset, self.pageYOffset];
	}
	else if (document.documentElement && document.documentElement.scrollTop) // IE 6 Strict
	{
		retArray = [document.documentElement.scrollLeft, document.documentElement.scrollTop];
	}
	else if (document.body)	// IE
	{
		retArray = [document.body.scrollLeft, document.body.scrollTop];
	}

	return retArray;
}
// window [width, height]
function qmGetWindowSize()
{
	var retArray;
	
	if (typeof(window.innerWidth) == 'number')	// FF, Opera (probably all except IE)
	{
		retArray = [window.innerWidth, window.innerHeight]
	}
	else if (document.documentElement && document.documentElement.clientWidth) //IE 6 strict
	{
		retArray = [document.documentElement.clientWidth, document.documentElement.clientHeight];
	}
	else if (document.body && document.body.clientWidth) //IE 4 compatible
	{
		retArray = [document.body.clientWidth, document.body.clientHeight];
	}

	return retArray;
}

if (typeof smpAddEvent != 'function')
{
	function smpAddEvent(obj, onwhat, fun)
	{
		if (obj.addEventListener)
		{
			obj.addEventListener(onwhat, fun, false);
		}
		else if (obj.attachEvent)
		{
			obj.attachEvent('on'+onwhat, fun);
		}
		else
		{
			// error
		}
	}
}

/* ------------------------------------------------------------------------ *\
	Correct size of the shade element(s) on window resize
\* ------------------------------------------------------------------------ */
window.sftJSmsgs = new Array();		// any sftJSmsg is added to his array inside .init() method
smpAddEvent(window, 'resize', function()
{
	if (window.sftJSmsgs.length<1)
	{
		return;
	}
	
	for (var i=window.sftJSmsgs.length-1; i>=0; i--)
	{
		var msg = window.sftJSmsgs[i];
		if (msg.msgEls[0].style.display == 'block')
		{
			msg.reInit();
		}
	}
});

//jsAlert('test')
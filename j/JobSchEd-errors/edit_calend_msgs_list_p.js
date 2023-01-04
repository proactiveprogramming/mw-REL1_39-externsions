/* ------------------------------------------------------------------------ *\
	Show and other methods for listing persons
\* ------------------------------------------------------------------------ */

oJobSchEd.oListPersons = new Object();

/* ------------------------------------------------------------------------ *\
	Show/build list window
\* ------------------------------------------------------------------------ */
oJobSchEd.oListPersons.show = function()
{
	// persons list
	var strList = '<h2>'+this.oParent.lang["header - persons"]+'</h2>';
	strList += '<ul style="text-align:left">';
	for (var i=0; i<this.oParent.arrPersons.length; i++)
	{
		var oP = this.oParent.arrPersons[i];
		strList += ''
			+'<li>'
				+'<a href="javascript:oJobSchEd.oListAct.show('+oP.intId.toString()+')" title="'
						+this.oParent.lang["title - list act"]
					+'">'
					+oP.strName
					+' ('+this.oParent.lang['button append - edit entries']+') '
					+'<img src="'+this.oParent.conf['img - list']+'" alt=" " />'
				+'</a>'
				+' &bull; '
				+'<a href="javascript:oJobSchEd.oModPerson.showEdit('+oP.intId.toString()+')" title="'
						+this.oParent.lang["title - edit"]
					+'">'
					+this.oParent.lang['button - edit person']
					+' <img src="'+this.oParent.conf['img - edit']+'" alt="'
						+this.oParent.lang['alt - mod']
					+'" />'
				+'</a>'
				+' '
				+'<a href="javascript:oJobSchEd.oModPerson.showDel('+oP.intId.toString()+')" title="'
						+this.oParent.lang["title - del"]
					+'">'
					+this.oParent.lang['button - delete person']
					+' <img src="'+this.oParent.conf['img - del']+'" alt="'
						+this.oParent.lang['alt - del']
					+'" />'
				+'</a>'
			+'</li>'
		;
	}
	strList += ''
		+'<li>'
			+'<a href="javascript:oJobSchEd.oModPerson.showAdd()" title="'
						+this.oParent.lang["title - add"]
					+'">'
				+this.oParent.lang['label - new person']
			+'</a>'
		+'</li>'
	;
	strList += '</ul>';

	// show form
	var msg = this.oMsg;
	msg.show(strList);
	msg.repositionMsgCenter();
}

/* ------------------------------------------------------------------------ *\
	Refresh list
\* ------------------------------------------------------------------------ */
oJobSchEd.oListPersons.refresh = function()
{
	// close previous
	this.oMsg.close();

	// show again
	this.show();
}
/* ------------------------------------------------------------------------ *\
	Show and other methods for listing tasks/activities
\* ------------------------------------------------------------------------ */

oJobSchEd.oListAct = new Object();

/* ------------------------------------------------------------------------ *\
	Show/build list window
\* ------------------------------------------------------------------------ */
oJobSchEd.oListAct.show = function(intPersonId)
{
	// remeber last (for refresh)
	if (typeof(intPersonId)=='undefined')
	{
		intPersonId = this.intLastPersonId;
	}
	this.intLastPersonId = intPersonId;
	
	// tasks list
	var i = this.oParent.indexOfPerson(intPersonId);
	// unexpected error (person should be known)
	if (i<0)
	{
		return;
	}
	var oP = this.oParent.arrPersons[i];
	var strList = '<h2>'+oP.strName+'</h2>';
	strList += '<ul style="text-align:left">';
	for (var j=0; j<oP.arrActivities.length; j++)
	{
		var oA = oP.arrActivities[j]
		if (typeof(oA)=='undefined')	// might be empty after del
		{
			continue;
		}
		strList += ''
			+'<li>'
				+'<a href="javascript:oJobSchEd.oModTask.showEdit('+oP.intId.toString()+', '+j.toString()+')" title="'
						+this.oParent.lang["title - edit"]
					+'">'
					+oA.strDateStart+" - "+oA.strDateEnd
					+": "+this.oParent.lang.activities[oA.intId].name
					+' '
					+'<img src="'+this.oParent.conf['img - edit']+'" alt=" " />'
				+'</a>'
				+' &bull; '
				+'<a href="javascript:oJobSchEd.oModTask.showDel('+oP.intId.toString()+', '+j.toString()+')" title="'
						+this.oParent.lang["title - del"]
					+'">'
					+this.oParent.lang['alt - del']
					+' <img src="'+this.oParent.conf['img - del']+'" alt="'
						+this.oParent.lang['alt - del']
					+'" />'
				+'</a>'
			+'</li>'
		;
	}
	strList += ''
		+'<li>'
			+'<a href="javascript:oJobSchEd.oModTask.showAdd('+oP.intId.toString()+')" title="'
						+this.oParent.lang["title - add"]
					+'">'
				+this.oParent.lang['label - new activity']
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
oJobSchEd.oListAct.refresh = function()
{
	// close previous
	this.oMsg.close();

	// show again
	this.show();
}
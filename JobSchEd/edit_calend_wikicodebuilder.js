/* ------------------------------------------------------------------------ *\
	Wiki code building methods
\* ------------------------------------------------------------------------ */

/* ------------------------------------------------------------------------ *\
	Gets contents of jsgantt tag
\* ------------------------------------------------------------------------ */
oJobSchEd.getContents = function()
{
	this.elEditArea = document.getElementById('wpTextbox1');
	var el = this.elEditArea;
	var m = el.value.match(this.conf.reGantMatch);
	if (m)
	{
		if (this.conf.isCodeIgnoredUpToLastXmlComment)
		{
			m[2] = m[2].replace(/^[\s\S]+<!--[\s\S]+?-->/, '');
		}
		return m[2];
	}
	return false;
}
/* ------------------------------------------------------------------------ *\
	Replace contents of jsgantt tag
\* ------------------------------------------------------------------------ */
oJobSchEd.setContents = function(strWikicode)
{
	var el = this.elEditArea;
	if (this.conf.isCodeIgnoredUpToLastXmlComment)
	{
		el.value.replace(this.conf.reGantMatch, function(a, prefix, content, sufix)
		{
			strWikicode = content.replace(/(^[\s\S]+<!--[\s\S]+?-->|)[\s\S]+/, '$1'+strWikicode);
		});
	}
	el.value = el.value.replace(this.conf.reGantMatch, "$1"+strWikicode+"$3");
}

/* ------------------------------------------------------------------------ *\
	Build wikicode from internal structures
\* ------------------------------------------------------------------------ */
oJobSchEd.buildWikicode = function()
{
	var strWikicode = '';
	for (var i=0; i<this.arrPersons.length; i++)
	{
		for (var j=0; j<this.arrPersons[i].arrActivities.length; j++)
		{
			if (typeof(this.arrPersons[i].arrActivities[j])=='undefined')	// might be empty after del
			{
				continue;
			}
			// preapre task object
			var oTask =
				{
					intPersonId		: this.arrPersons[i].intId,
					strPersonName	: this.arrPersons[i].strName,
					strDateStart	: this.arrPersons[i].arrActivities[j].strDateStart,
					strDateEnd		: this.arrPersons[i].arrActivities[j].strDateEnd,
					intActivityId	: this.arrPersons[i].arrActivities[j].intId
				}
			// render and add code
			strWikicode += this.buildTaskcode(oTask);
		}
	}
	return strWikicode + "\n";
}

/* ------------------------------------------------------------------------ *\
	Build single task code
	
	Returns:
	'' (empty str) on error
	wiki code (XML) for the task
	
	Ouput of nodeTask:
    <pID>10</pID>
    <pName>Maciek</pName>
    <pStart>2010-07-15</pStart>
    <pEnd>2010-07-30</pEnd>
    <pColor>0000ff</pColor>
    <pRes>Urlop</pRes>
\* ------------------------------------------------------------------------ */
oJobSchEd.buildTaskcode = function(oTask)
{
	var strWikiCode = '';

	try
	{
		strWikiCode = '\n<task>'
			+'\n\t<pID>'+oTask.intPersonId+'</pID>'
			+'\n\t<pName>'+oTask.strPersonName+'</pName>'
			+'\n\t<pStart>'+oTask.strDateStart+'</pStart>'
			+'\n\t<pEnd>'+oTask.strDateEnd+'</pEnd>'
			+'\n\t<pColor>'+this.lang.activities[oTask.intActivityId].color+'</pColor>'
			+'\n\t<pRes>'+this.lang.activities[oTask.intActivityId].name+'</pRes>'
			+'\n</task>'
		;
	}
	catch (e)
	{
		jsAlert(this.lang["gantt build error - at task"]
			.replace(/%pID%/g, oTask.intPersonId)
			.replace(/%pName%/g, oTask.strPersonName)
			.replace(/%errDesc%/g, e.description)
		);
		return '';
	}

	return strWikiCode;
}
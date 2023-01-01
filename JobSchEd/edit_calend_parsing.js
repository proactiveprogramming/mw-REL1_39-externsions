/* ------------------------------------------------------------------------ *\
	Parser methods
\* ------------------------------------------------------------------------ */

/* ------------------------------------------------------------------------ *\
	preparse XML to an XML document DOM
\* ------------------------------------------------------------------------ */
oJobSchEd.parseToXMLDoc = function(strWikicode)
{
	strWikicode = "<root>"+strWikicode+"</root>";
	var docXML;
	if (window.DOMParser)
	{
		var parser = new DOMParser();
		docXML = parser.parseFromString(strWikicode, "text/xml");
	}
	else // Internet Explorer
	{
		docXML = new ActiveXObject("Microsoft.XMLDOM");
		docXML.async = "false";
		docXML.loadXML(strWikicode);
	}
	return docXML;
}

/* ------------------------------------------------------------------------ *\
	Parse jsgantt tag contents into inner structures
	
	false on error
\* ------------------------------------------------------------------------ */
oJobSchEd.parse = function(strWikicode)
{
	var docXML = this.parseToXMLDoc(strWikicode);
	var elsTasks = docXML.getElementsByTagName('task');
	this.arrPersons = new Array();
	for (var i=0; i<elsTasks.length; i++)
	{
		var oTask = this.preParseTask(elsTasks[i]);
		if (oTask===false)
		{
			return false;
		}
		this.addTask (oTask);
	}
	return true;
}

/* ------------------------------------------------------------------------ *\
	Pre parse single task/activity node
	
	Returns:
	false on error
	oTask on success (cJobSchEdTask)

	Expected content of nodeTask:
    <pID>10</pID>
    <pName>Maciek</pName>
    <pStart>2010-07-15</pStart>
    <pEnd>2010-07-30</pEnd>
    <pColor>0000ff</pColor>
    <pRes>Urlop</pRes>
\* ------------------------------------------------------------------------ */
oJobSchEd.preParseTask = function(nodeTask)
{
	var oTask = new Object();
	
	// osoba
	try
	{
		oTask.intPersonId = parseInt(nodeTask.getElementsByTagName('pID')[0].textContent);
		oTask.strPersonName = nodeTask.getElementsByTagName('pName')[0].textContent;
	}
	catch (e)
	{
		jsAlert(this.lang["gantt parse error - no id and name at nr"].replace(/%i%/g, i));
		return false;
	}
	try
	{
		// daty
		oTask.strDateStart = nodeTask.getElementsByTagName('pStart')[0].textContent;
		oTask.strDateEnd = nodeTask.getElementsByTagName('pEnd')[0].textContent;
		// rodzaj (nie)aktywności
		var pColor = nodeTask.getElementsByTagName('pColor')[0].textContent;
		var pRes = nodeTask.getElementsByTagName('pRes')[0].textContent;
		oTask.intActivityId = this.getActivityId(pRes, pColor);
		if (oTask.intActivityId<0)
		{
			jsAlert(this.lang["gantt parse error - unknow activity"]
				.replace(/%pRes%/g, pRes)
				.replace(/%pColor%/g, pColor)
			);
			return false;
		}
	}
	catch (e)
	{
		jsAlert(this.lang["gantt parse error - at task"]
			.replace(/%pID%/g, oTask.intPersonId)
			.replace(/%pName%/g, oTask.strPersonName)
		);
		return false;
	}

	return oTask;
}
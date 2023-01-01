// <nowiki>
/* ------------------------------------------------------------------------ *\
    Job Schedule Edit

	Needed external modules:
	* JSWikiGantt ver. 0.3.0 or higher
	* sftJSmsg ver 0.3.0 or higher
 
	Description:
	-
 
    Copyright:  ©2010-2022 Maciej Jaros (pl:User:Nux, en:User:EcceNux)
     Licencja:  GNU General Public License v2
                http://opensource.org/licenses/gpl-license.php
\* ------------------------------------------------------------------------ */
//  wersja:
	var tmp_VERSION = '0.10.3';  // = oJobSchEd.version = oJobSchEd.ver
// ------------------------------------------------------------------------ //

/* =====================================================
	Object Init
   ===================================================== */
/*
if (oJobSchEd!=undefined)
{
	jsAlert('Błąd krytyczny - konflikt nazw!\n\nJeden ze skryptów używa już nazwy oJobSchEd jako zmienną globalną.');
}
*/
var oJobSchEd = new Object();
oJobSchEd.ver = oJobSchEd.version = tmp_VERSION;

oJobSchEd.conf = {"":""
	,strFallbackLang : 'en'                // Fallback language code
	,strLang         : wgContentLanguage   // Language to be used (note this probably shouldn't be user selectable, should be site wide)
	,isAutoAddLogged : false        // Automatically adds a logged in person if login is not found
	                               // Note that this doesn't mean that any task is added and so diagram will be changed only if the users adds a task.
	,strFormat : 'Y-m-d'		// date format for date-functions
	,strFormatJQ : 'yy-mm-dd'	// date format for JQuery date-picker
	,reGantMatch : /(<jsgantt[^>]*>)([\s\S]+)(<\/jsgantt>)/	// MUST match exactly 3 parts: prefix, content, sufix
	,isCodeIgnoredUpToLastXmlComment : true // Allows you to insert code (e.g. Holidays) that will not be modified. Insert them above other task and end with an XML comment.
	,isActivitiesIdentfiedByName : true // Allows colors to be different then in the setup.
	                                    // Note that colors will be changed upon output to those setup below.
	// allowed gantt tags? -> error when unsupported tags are found (to avoid editing non-JobSch diagrams)
	,"img - edit" : 'extensions/JobSchEd/img/edit.png'
	,"img - list" : 'extensions/JobSchEd/img/list.png'
	,"img - del"  : 'extensions/JobSchEd/img/del.png'
}
//
// i18n
//
oJobSchEd.lang = {"":""
	,'en' : {"":""
		,"button label" : "Edit calendar"
		,"gantt not found"                          : "There seems to be no calendar here. Add a &lt;jsgantt autolink='0'&gt;&lt;/jsgantt&gt; tag, if you want to start."
		,"gantt parse error - general"              : "Error parsing the gantt diagram code. This diagram is probably not a calendar."
		,"gantt parse error - no id and name at nr" : "Error parsing code at task number %i%. This calendar is either weird or broken."
		,"gantt parse error - at task"              : "Error parsing code at task with id %pID% (name: %pName%). This diagram is probably not a calendar or is broken."
		,"gantt parse error - unknow activity"      : "Error! Unknow activity (name: %pRes%, color: %pColor%). This diagram is probably not a calendar or is broken."
		,"gantt build error - at task"              : "Error building wikicode at task with id %pID% (name: %pName%).\nError: %errDesc%."
		,"gantt add error - unknown person"         : "Error! This person was not found. Are you sure you already added this?"
		,"header - add"         : "Add an entry"
		,"header - edit"        : "Edit an entry"
		,"header - persons"     : "Choose a person"
		,"header - del"         : "Are sure you want to delete this?"
		,"label - person"       : "Person"
		,"label - activity"     : "Type"
		,"label - date start"   : "Start"
		,"label - date end"     : "End"
		,"label - new activity" : "add an entry"
		,"label - new person"   : "add a person"
		,"button append - edit entries"  : "edit entries"
		,"button - edit person"     : "Edit person"
		,"button - delete person"   : "Delete person"
		,"alt - mod"            : "Change"
		,"alt - del"            : "Delete"
		,"close button label"   : "Close"
		,"title - list act"     : "Show this person's entries"
		,"title - edit"         : "Edit"
		,"title - add"          : "Add"
		,"title - del"          : "Delete"
		,"activities" : [
			{name: "Time off", color:"00cc00"},
			{name: "Delegation", color:"0000cc"},
			{name: "Sickness", color:"990000"},
		]
	}
	,'pl' : {"":""
		,"button label" : "Edytuj kalendarz"
		,"gantt not found"                          : "Na tej stronie nie znaleziono kalendarza. Dodaj tag &lt;jsgantt autolink='0'&gt;&lt;/jsgantt&gt;, aby móc rozpocząć edycję."
		,"gantt parse error - general"              : "Błąd parsowania kodu. Ten diagram prawdopodobnie nie jest kalendarzem."
		,"gantt parse error - no id and name at nr" : "Błąd parsowania kodu przy zadaniu numer %i%. Kod diagramu jest nietypowy, albo uszkodzony."
		,"gantt parse error - at task"              : "Błąd parsowania kodu przy zadaniu o id %pID% (nazwa: %pName%). Ten diagram nie jest kalendarzem, albo są w nim błędy."
		,"gantt parse error - unknow activity"      : "Błąd! Nieznana aktywność (nazwa: %pRes%, kolor: %pColor%). Ten diagram nie jest kalendarzem, albo są w nim błędy."
		,"gantt build error - at task"              : "Błąd budowania wiki-kodu przy zadaniu o id %pID% (nazwa: %pName%).\nBłąd: %errDesc%."
		,"gantt add error - unknown person"         : "Błąd! Wybrana osoba nie została znaleziona. Czy na pewno dodałeś(-aś) ją wcześniej?"
		,"header - add"         : "Dodaj wpis"
		,"header - edit"        : "Edytuj wpis"
		,"header - persons"     : "Wybierz osobę"
		,"header - del"         : "Czy na pewno chcesz usunąć?"
		,"label - person"       : "Osoba"
		,"label - activity"     : "Typ"
		,"label - date start"   : "Początek"
		,"label - date end"     : "Koniec"
		,"label - new activity" : "dodaj wpis"
		,"label - new person"   : "dodaj osobę"
		,"button append - edit entries" : "zmień wpisy"
		,"button - edit person"      : "Zmień osobę"
		,"button - delete person"    : "Usuń osobę"
		,"alt - mod"            : "Zmień"
		,"alt - del"            : "Usuń"
		,"close button label"   : "Zamknij"
		,"title - list act"     : "Pokaż wpisy osoby"
		,"title - edit"         : "Edytuj"
		,"title - add"          : "Dodaj"
		,"title - del"          : "Usuń"
		,"activities" : [
			{name: "Urlop", color:"00cc00"},
			{name: "Delegacja", color:"0000cc"},
			{name: "Choroba", color:"990000"},
		]
	}
}

/* ------------------------------------------------------------------------ *\
	Add edit button and init messages
\* ------------------------------------------------------------------------ */
oJobSchEd.init = function()
{
	//
	// Choose i18n object
	//
	if (this.conf.strLang in this.lang)
	{
		this.lang = this.lang[this.conf.strLang]
	}
	else
	{
		this.lang = this.lang[this.conf.strFallbackLang]
	}

	//
	// Add buttons and forms/messages
	//
	
	// edit button
	this.addEdButton()

	// task form
	var msg = new sftJSmsg();
	msg.repositionMsgCenter();
	msg.styleWidth = 500;
	msg.styleZbase += 30;
	msg.showCancel = true;
	msg.autoOKClose = false;
	msg.createRegularForm = false;
	this.oModTask.oMsg = msg;
	this.oModTask.oParent = this;

	// person form
	var msg = new sftJSmsg();
	msg.repositionMsgCenter();
	msg.styleWidth = 500;
	msg.styleZbase += 30;
	msg.showCancel = true;
	msg.autoOKClose = false;
	msg.createRegularForm = false;
	this.oModPerson.oMsg = msg;
	this.oModPerson.oParent = this;

	// persons list
	var msg = new sftJSmsg();
	msg.repositionMsgCenter();
	msg.styleWidth = 700;
	msg.styleZbase += 10;
	msg.showCancel = false;
	msg.lang['OK'] = this.lang["close button label"];
	msg.createRegularForm = false;
	this.oListPersons.oMsg = msg;
	this.oListPersons.oParent = this;

	// tasks of a person list
	var msg = new sftJSmsg();
	msg.repositionMsgCenter();
	msg.styleWidth = 500;
	msg.styleZbase += 20;
	msg.showCancel = false;
	msg.lang['OK'] = this.lang["close button label"];
	msg.createRegularForm = false;
	this.oListAct.oMsg = msg;
	this.oListAct.oParent = this;
	
	// autoedit
	if (location.href.search(/[&?]jsganttautoedit=1/)>=0)
	{
		this.startEditor();
	}
}
if (wgAction=="edit" || wgAction=="submit")
{
	addOnloadHook(function() {oJobSchEd.init()});
}

/* ------------------------------------------------------------------------ *\
	Add edit button
\* ------------------------------------------------------------------------ */
oJobSchEd.addEdButton = function()
{
	var elTB = document.getElementById('toolbar');
	if (!elTB)
	{
		return;
	}
	
	var nel = document.createElement('a');
	nel.href = "javascript:oJobSchEd.startEditor()";
	nel.style.cssText = "float:right";
	nel.appendChild(document.createTextNode(this.lang["button label"]));
	elTB.appendChild(nel);
}

/* ------------------------------------------------------------------------ *\
	Init internal structures and show the main editor's window
\* ------------------------------------------------------------------------ */
oJobSchEd.startEditor = function()
{
	// read wiki code
	var strWikicode = this.getContents();
	if (strWikicode===false)
	{
		jsAlert(this.lang["gantt not found"])
	}
	// parse code to internal structures
	if (!this.parse(strWikicode))	// on errors messages are displayed inside parse()
	{
		return;
	}

	// auto add logged in user
	if (this.conf.isAutoAddLogged && typeof(wgUserName)=='string' && wgUserName.length)
	{
		if (this.firstIdOfPersonByName(wgUserName)===false)
		{
			this.addPerson(wgUserName);
		}
	}

	// main editor's window - list persons
	this.oListPersons.show();
}

/* ------------------------------------------------------------------------ *\
	Find person in the this.arrPersons array
	
	index when found, -1 if not found
\* ------------------------------------------------------------------------ */
oJobSchEd.indexOfPerson = function(intPersonId)
{
	for (var i=0; i<this.arrPersons.length; i++)
	{
		if (this.arrPersons[i] && this.arrPersons[i].intId==intPersonId)
		{
			return i;
		}
	}
	return -1;
}

/* ------------------------------------------------------------------------ *\
	Find person in the this.arrPersons array by name
	
	@return first ID when found, false if not found
	@warning note that you should test with === false to check if a record was found
\* ------------------------------------------------------------------------ */
oJobSchEd.firstIdOfPersonByName = function(strPersonName)
{
	for (var i=0; i<this.arrPersons.length; i++)
	{
		if (this.arrPersons[i] && this.arrPersons[i].strName==strPersonName)
		{
			return this.arrPersons[i].intId;
		}
	}
	return false;
}

/* ------------------------------------------------------------------------ *\
	Get activity id (index) from color and resource name
	-1 => unknown
\* ------------------------------------------------------------------------ */
oJobSchEd.getActivityId = function(pRes, pColor)
{
	//"activities"
	for (var i=0; i<this.lang.activities.length; i++)
	{
		// name must be matched, color configurable
		if (this.lang.activities[i].name == pRes 
			&& (this.conf.isActivitiesIdentfiedByName || this.lang.activities[i].color == pColor)
		)
		{
			return i;
		}
	}
	return -1;
}

/* ------------------------------------------------------------------------ *\
	Add task to the internal persons array
\* ------------------------------------------------------------------------ */
oJobSchEd.addTask = function(oTask)
{
	var intPer = this.indexOfPerson (oTask.intPersonId);
	// new person?
	if (intPer==-1)
	{
		intPer = this.arrPersons.length;
		this.arrPersons[intPer] = {
			intId : oTask.intPersonId,
			strName : oTask.strPersonName,
			arrActivities : new Array()
		}
	}
	// add activity
	this.arrPersons[intPer].arrActivities[this.arrPersons[intPer].arrActivities.length] = {
		strDateStart : oTask.strDateStart,
		strDateEnd : oTask.strDateEnd,
		intId : oTask.intActivityId
	}
}

/* ------------------------------------------------------------------------ *\
	Add person to the internal persons array
\* ------------------------------------------------------------------------ */
oJobSchEd.addPerson = function(strPersonName)
{
	var intPer = this.arrPersons.length;
	var intDefaultStep = 10;
	// new id
	var intPersonId = (intPer>0) ? this.arrPersons[intPer-1].intId + intDefaultStep : intDefaultStep;
	while (this.indexOfPerson (intPersonId)!=-1)
	{
		intPersonId+=10;
	}
	// add
	this.arrPersons[intPer] = {
		intId : intPersonId,
		strName : strPersonName,
		arrActivities : new Array()
	}
}

/* ------------------------------------------------------------------------ *\
	Change task in the internal persons array
\* ------------------------------------------------------------------------ */
oJobSchEd.setTask = function(oTask, intPersonId, intActIndex)
{
	var intPer = this.indexOfPerson (intPersonId);
	// person not found?
	if (intPer==-1)
	{
		return false;
	}
	// change activity
	this.arrPersons[intPer].arrActivities[intActIndex] = {
		strDateStart : oTask.strDateStart,
		strDateEnd : oTask.strDateEnd,
		intId : oTask.intActivityId
	}
	return true;
}

/* ------------------------------------------------------------------------ *\
	Change person in the internal persons array
\* ------------------------------------------------------------------------ */
oJobSchEd.setPerson = function(strPersonName, intPersonId)
{
	var intPer = this.indexOfPerson (intPersonId);
	// person not found?
	if (intPer==-1)
	{
		return false;
	}
	// change person
	this.arrPersons[intPer].strName = strPersonName;
	return true;
}

/* ------------------------------------------------------------------------ *\
	Remove task from the internal persons array
\* ------------------------------------------------------------------------ */
oJobSchEd.delTask = function(intPersonId, intActIndex)
{
	var intPer = this.indexOfPerson (intPersonId);
	// person not found?
	if (intPer==-1)
	{
		return false;
	}
	// remove activity
	this.arrPersons[intPer].arrActivities[intActIndex] = undefined;
	return true;
}

/* ------------------------------------------------------------------------ *\
	Remove person from the internal persons array
\* ------------------------------------------------------------------------ */
oJobSchEd.delPerson = function(intPersonId)
{
	var intPer = this.indexOfPerson (intPersonId);
	// person not found?
	if (intPer==-1)
	{
		return false;
	}
	// remove
	this.arrPersons[intPer] = undefined;
	// reindex to remove undefines
	this.arrPersons.myReIndexArray()
	return true;
}

/* ------------------------------------------------------------------------ *\
	Reindex array with undefined values
\* ------------------------------------------------------------------------ */
Array.prototype.myReIndexArray = function()
{
	for (var i=0; i<this.length; i++)
	{
		if (this[i]==undefined)
		{
			// search for defined...
			for (var j=i; j<this.length; j++)
			{
				if (this[j]==undefined)
				{
					continue;
				}
				this[i]=this[j];
				this[j]=undefined;
				break;
			}
		}
	}
	// fix length
	while (this.length > 0 && this[this.length-1] == undefined)
	{
		this.length--;
	}
}

// </nowiki>
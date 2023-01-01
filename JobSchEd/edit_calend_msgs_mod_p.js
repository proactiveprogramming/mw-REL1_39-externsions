/* ------------------------------------------------------------------------ *\
	Show/submit methods for person modification (add&edit&delete)
\* ------------------------------------------------------------------------ */

oJobSchEd.oModPerson = new Object();

/* ------------------------------------------------------------------------ *\
	Show/build add person window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.showAdd = function()
{
	// get/build activities and persons lables
	//this.buildLabels();
	
	// defaults
	this.oParent.oNewPerson = {
		strPersonName : ''
	};

	// fields setup
	var arrFields = this.getArrFields('oJobSchEd.oNewPerson');
	var strHTML = this.oParent.createForm(arrFields, this.oParent.lang['header - add']);

	// show form
	var msg = this.oMsg;
	msg.show(strHTML, 'oJobSchEd.oModPerson.submitAdd()');
	msg.repositionMsgCenter();
}

/* ------------------------------------------------------------------------ *\
	Submit add person window
	
	TODO: some validation?
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.submitAdd = function()
{
	// add person
	this.oParent.addPerson (this.oParent.oNewPerson.strPersonName);
	
	// common stuff (rebuild, refresh...)
	this.submitCommon();
}

/* ------------------------------------------------------------------------ *\
	Show/build edit person window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.showEdit = function(intPersonId)
{
	// defaults
	var intPer = this.oParent.indexOfPerson(intPersonId);
	this.oParent.oNewPerson = {
		strPersonName : this.oParent.arrPersons[intPer].strName
	};

	// fields setup
	var arrFields = this.getArrFields('oJobSchEd.oNewPerson');
	var strHTML = this.oParent.createForm(arrFields, this.oParent.lang['header - edit']);

	// show form
	var msg = this.oMsg;
	msg.show(strHTML, 'oJobSchEd.oModPerson.submitEdit('+intPersonId+')');
	msg.repositionMsgCenter();
}

/* ------------------------------------------------------------------------ *\
	Submit edit person window
	
	TODO: some validation of dates?
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.submitEdit = function(intPersonId)
{
	// add person
	this.oParent.setPerson (this.oParent.oNewPerson.strPersonName, intPersonId);

	// common stuff (rebuild, refresh...)
	this.submitCommon();
}

/* ------------------------------------------------------------------------ *\
	Show/build del person window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.showDel = function(intPersonId)
{
	// defaults
	var intPer = this.oParent.indexOfPerson(intPersonId);

	// fields setup
	var strHTML = "<h2>"+this.oParent.lang['header - del']+"</h2>"
		+ this.oParent.arrPersons[intPer].strName;

	// show form
	var msg = this.oMsg;
	msg.show(strHTML, 'oJobSchEd.oModPerson.submitDel('+intPersonId+')');
	msg.repositionMsgCenter();
}

/* ------------------------------------------------------------------------ *\
	Submit del person window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.submitDel = function(intPersonId)
{
	// add person
	this.oParent.delPerson (intPersonId);

	// common stuff (rebuild, refresh...)
	this.submitCommon();
}

/* ------------------------------------------------------------------------ *\
	Build labels for this form
\* ------------------------------------------------------------------------ *
oJobSchEd.oModPerson.buildLabels = function()
{
	// persons labels
	this.arrPersonLbls = new Array();
	for (var i=0; i<this.oParent.arrPersons.length; i++)
	{
		this.arrPersonLbls[this.arrPersonLbls.length] = {
			value	: this.oParent.arrPersons[i].intId,
			lbl		: this.oParent.arrPersons[i].strName
		};
	}
	// activities labels
	this.arrActivityLbls = new Array();
	for (var i=0; i<this.oParent.lang.activities.length; i++)
	{
		this.arrActivityLbls[this.arrActivityLbls.length] = {
			value	: i,
			lbl		: this.oParent.lang.activities[i].name
		};
	}
}

/* ------------------------------------------------------------------------ *\
	Get fields array for this form
	
	strNewPersonObject = 'oJobSchEd.oNewPerson'
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.getArrFields = function(strNewPersonObject)
{
	return [
		{type:'text', maxlen: 100, width: 100, lbl: this.oParent.lang['label - person']
			, value:this.oParent.oNewPerson.strPersonName
			, jsUpdate:strNewPersonObject+'.strPersonName = this.value'}
	];
}

/* ------------------------------------------------------------------------ *\
	Common stuff done at the end of submit
\* ------------------------------------------------------------------------ */
oJobSchEd.oModPerson.submitCommon = function()
{
	// build
	var strWikicode = this.oParent.buildWikicode();
	// output
	this.oParent.setContents(strWikicode);
	// close
	this.oMsg.close();
	
	// refresh window
	this.oParent.oListPersons.refresh();
}
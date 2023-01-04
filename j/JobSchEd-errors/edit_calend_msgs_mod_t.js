/* ------------------------------------------------------------------------ *\
	Show/submit methods for task/activity modification (add&edit&delete)
\* ------------------------------------------------------------------------ */

oJobSchEd.oModTask = new Object();

/* ------------------------------------------------------------------------ *\
	Show/build add task window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.showAdd = function(intPersonId)
{
	// get/build activities and persons lables
	this.buildLabels();
	
	// defaults
	var now = new Date();
	this.oParent.oNewTask = {
		intPersonId : (typeof(intPersonId)=='undefined' ? this.arrPersonLbls[0].value : intPersonId),
		intActivityId : this.arrActivityLbls[0].value,
		strDateStart : now.dateFormat(this.oParent.conf.strFormat),
		strDateEnd : now.dateFormat(this.oParent.conf.strFormat)
	};

	// fields setup
	var arrFields = this.getArrFields('oJobSchEd.oNewTask');
	var strHTML = this.oParent.createForm(arrFields, this.oParent.lang['header - add']);

	// show form
	var msg = this.oMsg;
	msg.show(strHTML, 'oJobSchEd.oModTask.submitAdd()');
	msg.repositionMsgCenter();

	jQuery( ".datepicker" ).datepicker({ dateFormat: this.oParent.conf.strFormatJQ });
}

/* ------------------------------------------------------------------------ *\
	Submit add task window
	
	TODO: some validation of dates?
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.submitAdd = function()
{
	// data parse
	this.oParent.oNewTask.intPersonId = parseInt(this.oParent.oNewTask.intPersonId);
	this.oParent.oNewTask.intActivityId = parseInt(this.oParent.oNewTask.intActivityId);
	var intP = this.oParent.indexOfPerson(this.oParent.oNewTask.intPersonId)
	if (intP!=-1)
	{
		this.oParent.oNewTask.strPersonName = this.oParent.arrPersons[intP].strName;
	}
	else
	{
		jsAlert(this.oParent.lang["gantt add error - unknown person"]);
		return;
	}

	// add task
	this.oParent.addTask (this.oParent.oNewTask);
	
	// common stuff (rebuild, refresh...)
	this.submitCommon();
}

/* ------------------------------------------------------------------------ *\
	Show/build edit task window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.showEdit = function(intPersonId, intActIndex)
{
	// get/build activities and persons lables
	this.buildLabels();
	
	// defaults
	var intPer = this.oParent.indexOfPerson(intPersonId);
	var oA = this.oParent.arrPersons[intPer].arrActivities[intActIndex];
	this.oParent.oNewTask = {
		intPersonId : intPersonId,
		intActivityId : oA.intId,
		strDateStart : oA.strDateStart,
		strDateEnd : oA.strDateEnd
	};
	
	// fields setup
	var arrFields = this.getArrFields('oJobSchEd.oNewTask');
	var strHTML = this.oParent.createForm(arrFields, this.oParent.lang['header - edit']);

	// show form
	var msg = this.oMsg;
	msg.show(strHTML, 'oJobSchEd.oModTask.submitEdit('+intPersonId+', '+intActIndex+')');
	msg.repositionMsgCenter();

	jQuery( ".datepicker" ).datepicker({ dateFormat: this.oParent.conf.strFormatJQ });
}
	
/* ------------------------------------------------------------------------ *\
	Submit edit task window
	
	TODO: some validation of dates?
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.submitEdit = function(intPersonId, intActIndex)
{
	var oNewTask = this.oParent.oNewTask;
	// data parse
	oNewTask.intPersonId = parseInt(oNewTask.intPersonId);
	oNewTask.intActivityId = parseInt(oNewTask.intActivityId);
	var intP = this.oParent.indexOfPerson(oNewTask.intPersonId)
	if (intP!=-1)
	{
		oNewTask.strPersonName = this.oParent.arrPersons[intP].strName;
	}
	else
	{
		jsAlert(this.oParent.lang["gantt add error - unknown person"]);
		return;
	}

	// person not changed? => simply change act.
	if (intPersonId==oNewTask.intPersonId)
	{
		this.oParent.setTask (oNewTask, intPersonId, intActIndex);
	}
	// => remove act. from the previous person and add a new one
	else
	{
		this.oParent.delTask (intPersonId, intActIndex);
		this.oParent.addTask (oNewTask);
	}
	
	// common stuff (rebuild, refresh...)
	this.submitCommon();
}

/* ------------------------------------------------------------------------ *\
	Show/build del task window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.showDel = function(intPersonId, intActIndex)
{
	// defaults
	var intPer = this.oParent.indexOfPerson(intPersonId);
	var oA = this.oParent.arrPersons[intPer].arrActivities[intActIndex];

	// fields setup
	var strHTML = "<h2>"+this.oParent.lang['header - del']+"</h2>"
		+oA.strDateStart+" - "+oA.strDateEnd
		+": "+this.oParent.lang.activities[oA.intId].name
	;

	// show form
	var msg = this.oMsg;
	msg.show(strHTML, 'oJobSchEd.oModTask.submitDel('+intPersonId+', '+intActIndex+')');
	msg.repositionMsgCenter();
}

/* ------------------------------------------------------------------------ *\
	Submit del task window
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.submitDel = function(intPersonId, intActIndex)
{
	// add person
	this.oParent.delTask (intPersonId, intActIndex);

	// common stuff (rebuild, refresh...)
	this.submitCommon();
}

/* ------------------------------------------------------------------------ *\
	Build labels for this form
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.buildLabels = function()
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
	
	strNewTaskObject = 'oJobSchEd.oNewTask'
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.getArrFields = function(strNewTaskObject)
{
	return [
		{type:'select', title: this.oParent.lang['label - person']
			, lbls : this.arrPersonLbls
			, value:this.oParent.oNewTask.intPersonId
			, jsUpdate:strNewTaskObject+'.intPersonId = this.value'},
		{type:'select', title: this.oParent.lang['label - activity']
			, lbls : this.arrActivityLbls
			, value:this.oParent.oNewTask.intActivityId
			, jsUpdate:strNewTaskObject+'.intActivityId = this.value'},
		{type:'date', maxlen: 10, lbl: this.oParent.lang['label - date start']
			, value:this.oParent.oNewTask.strDateStart
			, jsUpdate:strNewTaskObject+'.strDateStart = this.value'},
		{type:'date', maxlen: 10, lbl: this.oParent.lang['label - date end']
			, value:this.oParent.oNewTask.strDateEnd
			, jsUpdate:strNewTaskObject+'.strDateEnd = this.value'}
	];
}

/* ------------------------------------------------------------------------ *\
	Common stuff done at the end of submit
\* ------------------------------------------------------------------------ */
oJobSchEd.oModTask.submitCommon = function()
{
	// build
	var strWikicode = this.oParent.buildWikicode();
	// output
	this.oParent.setContents(strWikicode);
	// close
	this.oMsg.close();
	
	// refresh window<del>s</del>
	//this.oParent.oListPersons.refresh();
	this.oParent.oListAct.refresh();
}
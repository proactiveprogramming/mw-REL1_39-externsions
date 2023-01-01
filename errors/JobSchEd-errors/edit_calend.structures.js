// Main internal stucture
arrPersons[]
{
	intId			: [int] unique ID of the person,
	strName			: [str] name of the person,
	arrActivities	: [oActivity1, oActivity2, ...]
}

// Activity used in the arrActivities array
cActivity
{
	strDateStart	: [str] start date of the activity,
	strDateEnd		: [str] end date of the activity,
	intId			: [int] numeric index in this.lang.activities
}

// "Task" with structure more typical to Gantt diagram
cTask
{
	intPersonId		: [int] unique ID of the person,
	strPersonName	: [str] name of the person,
	strDateStart	: [str] start date of the activity,
	strDateEnd		: [str] end date of the activity,
	intActivityId	: [int] numeric index in this.lang.activities
}

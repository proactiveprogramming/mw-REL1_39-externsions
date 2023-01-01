/**
	Pobieranie œwi¹t z:
	http://www.kalbi.pl/
	http://www.kalbi.pl/kalendarz-2019
	
	Przygotowanie wikikodu w grafiku
	----------------------------------------
	1. (opcjonalnie) Wyrzuæ niektóre stare œwiêta i zarchiwizuj urlopy.
	2. Zamknij poprzednie œwiêta (ustaw: `<pOpen>0</pOpen>`).
	
	Eksport i import œwi¹t
	----------------------------------------
	1. Ustaw poni¿ej `currentYear`.
	2. WejdŸ na kalendarz na www.kalbi.pl na dany rok.
	3. Copy paste poni¿eszego do konsoli JS.
	4. Skopiuj wikikod z utworzonego pola textarea (powinno byæ na górze strony).
	5. Wklej przed pierwszym urlopem.
	
*/
var currentYear = 2022;	// bie¿¹cy/parsowany rok

// sta³e/generowane
var idParent = parseInt(`${currentYear}000`);	// id taska grupuj¹cego (tj. rêcznie dodanego taska z nazw¹ w rodzaju "Œwiêta 2017")
var idBase = idParent;							// id ostatniego zajêtego id (idBase+1 = pierwsze ID dla nowego œwiêta)

// tpl grupy
var tplGroup = `
	<!-- ${currentYear} -->
	<task>
		<pID>${idParent}</pID>
		<pGroup>1</pGroup>
		<pOpen>1</pOpen>
		<pName>Œwiêta ${currentYear}</pName>
		<pRes>Wszyscy</pRes>
	</task>
`;

// tpl œwiêta
var tpl = ''
	+'\n	<task>'
	+'\n		<pID>%id%</pID>'
	+'\n		<pParent>'+idParent+'</pParent>'
	+'\n		<pStart>%date%</pStart>'
	+'\n		<pEnd>%date%</pEnd>'
	+'\n		<pColor>00cc00</pColor>'
	+'\n		<pRes>%name%</pRes>'
	+'\n		<pName>%name%</pName>'
	+'\n	</task>'

/**
	Helper function to get XML for the Holiday
*/
function getHolyString(id, name, date)
{
	return tpl
		.replace(/%id%/g, id)
		.replace(/%name%/g, name)
		.replace(/%date%/g, date)
	;
}

function twoDigitString(num) {
	num = +num;
	return ( num<10 ? '0' + num.toString() : num.toString() );
}

var str = tplGroup;
var lastId = idBase;

var monthsTran = ['0', 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'wrzesnia', 'pazdziernika', 'listopada', 'grudnia']

var hrefToDate = {
	from: /.*[^0-9]([0-9]{1,2})-([a-z]+).*/i,
	to: function(a, day, monthName) {
		var month = monthsTran.indexOf(monthName);
		if (month<0) {
			console.error('Unknown:' + monthName);
		}
		day = +day;
		return currentYear + "-" + twoDigitString(month) + "-" + twoDigitString(day);
	}
}
var holidayElements = [];	// for debug
// (!) holiday selector
// holyday ffree
//$('.yearCalM-year .yearCal_free').each(function()
$('.year2 .ffree').each(function()
{
	//console.log('adding: ', this);
	var $el = $('a.festtip', this);
	if (!$el.length) {
		console.warn('Link el not found. Parent: ', this);
		return true;
	}
	var el = $el[0];
	holidayElements.push(el);
	var date = el.href.replace(hrefToDate.from, hrefToDate.to);
	var name = el.title;
	name = name
		.replace(/\s*<.+?>\s*/g, ', ')
		.replace(/^\s+/, '')
		.replace(/\s+$/, '')
	;
	lastId++;
	str += getHolyString(lastId, name, date);
});
if (holidayElements.length < 10) {
	console.warn('holidayElements found seems too small:', holidayElements);
}

// dump to textarea
var el = document.getElementById('JobSchEd_tool_getHolidays');
if (!el) {
	el = document.createElement('textarea');
	el.id='JobSchEd_tool_getHolidays';
	el.style.cssText="position:absolute; left:0; top:0; z-index: 10000; width: 50%; height: 100px;";
	document.body.appendChild(el);
}
//
el.value=str;
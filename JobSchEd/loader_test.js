/*
-----------------------
THE LOADER CHECK:
-----------------------
*/
	// simple
	var z = ""+
		"djfhjshd // test"
	;
	var z = "http://abc.def";
	var z = 'http://abc.def';
	// evil
	var x = /a"aa\/\/asa/ " \
	//\
	";
	var x = /a"aa\/\/asa/ ' \
	//\
	';
	// very evil
	var x = /a"aa\/\/asa/ ' \
	// "aa"\
	';
	var x = /a"aa\/\/asa/ " \
	// 'xx'\
	";
	var x = /a"aa\/\/asa/ ' \
	// "aa"';
	var x = /a"aa\/\/asa/ " \
	// 'xx'";
	//
	var x = /aa\/*a*/;
	// a bit evil
	var x = /aa\/*a*/
	;
	
//
// This should be removed
//

// "This too"

// 'And this'

// AndThisToo ('');

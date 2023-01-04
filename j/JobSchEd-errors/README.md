JobSchEd
========

This extension provides a user interface for editing something you might call an activities calendar or a job schedule. Under the hood it uses [JSWikiGantt](https://github.com/Eccenux/JSWikiGantt) so you need it installed if you want to use this.

For more info please see:
https://www.mediawiki.org/wiki/Extension:JobSchEd

Installation
------------

1. Download the extension files and place them under <tt>extensions/JobSchEd</tt>
2. At the end of LocalSettings.php, add:
	`require_once("$IP/extensions/JobSchEd/JobSchEd.php");`
3. Installation can now be verified through <tt>Special:Version</tt> on your wiki


Renaming people
---------------

Search regexp:
```
<pID>(\d{3})</pID>(\s*)<pName>([^<]+)</pName>
```
Replace function (JS):
```
function(a, pid, space, name) {
	var pmap = {
		210:{before:{id:'210',name:'Joe'},          after:{id:'100',name:'Joe Doe'}},
		170:{before:{id:'170',name:'Bob'},          after:{id:'110',name:'Robert Sky'}},
		200:{before:{id:'200',name:'Maciej'},       after:{id:'200',name:'Maciej Jaros'}}, 
	};

	if (typeof pmap[pid] == 'object') {
		var p = pmap[pid];
		pid = p.after.id;
		name = p.after.name;
		return `<pID>${pid}</pID>${space}<pName>${name}</pName>`;
	}
	return a;
}
```
const fs = require('fs');
fs.readFile('../data/NodosOntology.xml', 'utf8', function (err,fileData) {
	if (err) {
		return console.log(err);
	}
	const regexp = /<title>(.+)<\/title>/g;
	const matches = [];

	match = regexp.exec(fileData);
	while (match) {
		matches.push(match[1]);
		match = regexp.exec(fileData);
	}

	console.log(matches.join('\n'));
});
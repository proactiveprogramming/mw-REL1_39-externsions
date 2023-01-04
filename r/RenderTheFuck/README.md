# RenderTheFuck
A MediaWiki extension to generate timeline graph.

# HowTo
Goto MediaWiki extension page [RenderTheFuck](https://www.mediawiki.org/wiki/Extension:RenderTheF%C3%BCck).

# About scripts
scripts | purpose
---- | ----
composer test | Lint PHP code by MediaWiki coding conventions
composer fix | Autofix PHP code by MediaWiki coding conventions
yarn uglify | build *.min.js
yarn cssmin | build *.min.css
yarn lint-js | Lint *.js
yarn lint-json | Lint examples/*.tfj

# Build steps
```
               browserify                uglify
render.es6   --------------> render.js ----------> render.min.js
timeline.es6 -----|
stack.es6    -----|
error.es6    -----+

               cssmin                       genils.php
timeline.css ----------> timeline.min.css --------------> styles.timeline.js
stack.css                stack.min.css                    styles.stack.js
```

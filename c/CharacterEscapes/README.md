# Character Escapes
Sometimes it is desired that wiki markup be parsed (or remain unparsed) under certain conditions.  Since certain characters or character sequences are processed before reaching the parser function, we have to use escapes to prevent markup being parsed prematurely.  MediaWiki does not have built-in mechanism for this, so we have to make our own:

* \l (**l**ess than) is translated to &lt;
* \g (**g**reater than) is translated to &gt;
* \o (**o**pen double curly braces) is translated to {<nowiki/>{
* \c (**c**lose double curly braces) is translated to }<nowiki/>}
* \p (**p**ipe) is translated to |
* \\ is translated to **\**
* \n is translated to a **n**ewline

The first two translations make it possible to embed a ''wiki tag extension'' into a parameter of a parser function call. The next three translations make it possible to ''call a template, invoke a magic word, or call a parser function'' which prevents them from executing until conditions dictate that the results of such a call will be displayed.  The next one is for times where you want to display text like "\p" without having it converted into a pipe, which is done by writing it as "\\p".  The last one is for tags that use newline characters as delimiters for parameters.  It allows a newline character to be passed as part of the parameter instead of indicating the beginning/ending of a parameter.

## Installation
Download and place the file(s) in a directory called CharacterEscapes in your extensions/ folder.
Add the following code at the bottom of your LocalSettings.php:

	wfLoadExtension( 'CharacterEscapes' );

Done - Navigate to Special:Version on your wiki to verify that the extension is successfully installed.


## Example
```
{{ #vardefine: i | 0 }}{{
  #while: expr
  | <esc>{{ #var: i }} < 5</esc>
  |* <esc>{{ #var: i }}{{ #vardefine: i | {{ #expr: {{ #var: i }} + 1 }} }}</esc>
}}
```

produces the following:

* 0
* 1
* 2
* 3
* 4

Note that the example uses the [variables](https://www.mediawiki.org/wiki/Extension:VariablesExtension) and [control structure functions](https://www.mediawiki.org/wiki/Extension:Control_Structure_Functions) extensions.

## Limitations
MediaWiki does not support nested tags of the same type (see [[bugzilla:1310|bug #1310]]).  Given the following:

```
<nowiki><esc>{{ #ifexpr:... | <esc>{{ templateB | param }}</esc> | param }}</esc></nowiki>
```

The text:

```
<nowiki>{{ #ifexpr:... | <esc>{{ templateB | param }}</nowiki>
```

is passed to the underlying function instead of:

```
<nowiki>{{ #ifexpr:... | <esc>{{ templateB | param }}</esc> | param }}</nowiki>
```

A workaround is to explicitly write out the nested escape sequences:

```
<nowiki><esc>{{ #ifexpr:... | \o templateB \p param \c | param }}</esc></nowiki>
```

Another solution is to apply the modification given in the discussion of [[bugzilla:1310|bug #1310]].

## Writing Extensions that Use Character Escapes
If you would like your extension to make use of character escapes, the class CharacterEscapes contains two static functions for replacing characters with escapes (`CharacterEscapesHooks::charEsc()`) and replacing escapes with characters (`CharacterEscapesHooks::charUnesc()`).

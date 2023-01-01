Done
----

* parse code to internal structures
* render wikicode from internal structures
	* oJobSchEd.buildWikicode
	* oJobSchEd.buildTaskcode
* Basic add task dialog
* Add missing labels
* More accurate names for methods
* Fix for select inputs (values were not updated)
* Task class? Could use something like new this.oTask() and some methods might be moved...;
	* Not really working... Moving oTask idea to cJobSchEdTask prototype.
	* Moved methods to different files for easier editing...
	* <del>Change current usage of loose objects to class instances usage</del> - not really needed...
	* Some clean up
* Dialogue with a list of all persons.
* Dialogue with a list of all tasks of a person.
* Move dialogues to subobjects.
* Refresh methods for dialogues (run after add/edit).
* Common from Add moved out...
* Edit dialogue based on add dialogue.
* Support changing persons in edit dialogue.
* Add person dialogue based on add task dialogue.
* Edit person dialogue.
* Reposition clean up (needs new version of sftJSmsg to work as planned)
* Look&feel more like on original mockups (+images).
* Delete task methods.
* Delete person methods.
* Refresh of fields problem (they are filled with previous values upon add => out of sync with oNewTask => unexpected)
* Use <del>the loader or</del> combine "modules" into one file with a simple loader.
* edit_calend_cTask -> edit_calend.structures as a more general info file on most important structures
* Minify core (as a module)
* Improve minification
	* remove in-line comments
	* remove vertical whitespace from EOL
* Correction of file change check.
* Lower memory load in loader - use fopen and fwrite rather then operating on the same string
* Loader returns a file name rather then adding a head item (+some clean up in variables)
* Inline comments evil code fix
* Loader tester
* simpler isChanged
* Enable adding persons to an empty diagram
* Titles for links
* Basic i18n
* Datepicker

10.x
* Allow inserting code (e.g. Holidays) that will not be modified with this tool (conf isCodeIgnoredUpToLastXmlComment).
* Labels for buttons.

Someday...
----------
* Sort tasks by start date upon build/on demand?

window.onload = function () {
    
    //If there is a previously created overlay, then get rid of it. Useful for naviagting in history
    $('#gantt_overlay').hide();

    window.oJSWikiGanttFrontEnd = {};

    Array.prototype.last = function () {
        return this[this.length - 1];
    };
    oJSWikiGanttFrontEnd.ver = oJSWikiGanttFrontEnd.version = '1.0.0';
    oJSWikiGanttFrontEnd.conf = {"" : ""
        , strFallbackLang : 'en'
        , strLang         : mw.config.values.wgContentLanguage   // Language to be used (note this probably shouldn't be user selectable, should be site wide)
        , isAutoAddLogged : true
                                       // Note that this doesn't mean that any task is added and so diagram will be changed only if the users adds a task.
        ,strFormat : 'Y-m-d'
        ,reGantMatch : /(<jsgantt[^<]*>)([\s\S]*)(<\/jsgantt>)/
        ,isActivitiesIdentfiedByName : true
        
        ,currentColor : '8CB6CE'    //dynamically assigned
        ,defaultChecked : false     //dynamically assigned
        ,defaultColor: '8CB6CE'     //dynamically assigned
        ,"img - edit" : ''
        ,"img - list" : 'extensions/JSWikiGanttFrontEnd/img/list.png' 
        ,"img - del"  : 'extensions/JSWikiGanttFrontEnd/img/x.png'
        ,marginSize : 20
    }

    oJSWikiGanttFrontEnd.lang = {"":""
        ,'en' : {"":""
            ,"button label" : "Edit Gantt Chart"
            ,"gantt not found"                          : "There seems to be no gantt chart here. Add a &lt;jsgantt&gt;&lt;/jsgantt&gt; tag, if you want to start."
            ,"gantt parse error - general"              : "Error parsing the gantt diagram code. This diagram is probably not a calendar."
            ,"gantt parse error - no id and name at nr" : "Error parsing code at task number %i%. This calendar is either weird or broken."
            ,"gantt parse error - at task"              : "Error parsing code at task with id %pID% (name: %pName%). This diagram is probably not a calendar or is broken."
            ,"gantt parse error - unknow activity"      : "Error! Unknow activity (name: %pRes%, color: %pColor%). This diagram is probably not a calendar or is broken."
            ,"gantt build error - at task"              : "Error building wikicode at task with id %pID% (name: %pName%).\nError: %errDesc%."
            ,"header - add"                             : "Add an entry"
            ,"header - edit"                            : "Edit an entry"
            ,"header - del"                             : "Are sure you want to delete this?"
            ,"label - activity"                         : "Type"
            ,"label - date start"                       : "Start"
            ,"label - date end"                         : "End"
            ,"label - new activity"                     : "add an entry"
            ,"alt - mod"                                : "Change"
            ,"alt - del"                                : "Delete"
            ,"close button label"                       : "Close"
            ,"title - edit"                             : "Edit"
            ,"title - add"                              : "Add"
            ,"title - del"                              : "Delete"


        }
        ,'pl' : {"":""
            ,"button label" : "Edytuj kalendarz"
            ,"gantt not found"                          : "Na tej stronie nie znaleziono kalendarza. Dodaj tag &lt;jsgantt autolink='0'&gt;&lt;/jsgantt&gt;, aby móc rozpocząć edycję."
            ,"gantt parse error - general"              : "Błąd parsowania kodu. Ten diagram prawdopodobnie nie jest kalendarzem."
            ,"gantt parse error - no id and name at nr" : "Błąd parsowania kodu przy zadaniu numer %i%. Kod diagramu jest nietypowy, albo uszkodzony."
            ,"gantt parse error - at task"              : "Błąd parsowania kodu przy zadaniu o id %pID% (nazwa: %pName%). Ten diagram nie jest kalendarzem, albo są w nim błędy."
            ,"gantt parse error - unknow activity"      : "Błąd! Nieznana aktywność (nazwa: %pRes%, kolor: %pColor%). Ten diagram nie jest kalendarzem, albo są w nim błędy."
            ,"gantt build error - at task"              : "Błąd budowania wiki-kodu przy zadaniu o id %pID% (nazwa: %pName%).\nBłąd: %errDesc%."
            ,"header - add"                             : "Dodaj wpis"
            ,"header - edit"                            : "Edytuj wpis"
            ,"header - del"                             : "Czy na pewno chcesz usunąć?"
            ,"label - activity"                         : "Typ"
            ,"label - date start"                       : "Początek"
            ,"label - date end"                         : "Koniec"
            ,"label - new activity"                     : "dodaj wpis"
            ,"alt - mod"                                : "Zmień"
            ,"alt - del"                                : "Usuń"
            ,"close button label"                       : "Zamknij"
            ,"title - edit"                             : "Edytuj"
            ,"title - add"                              : "Dodaj"
            ,"title - del"                              : "Usuń"

        }
    }

    /* ------------------------------------------------------------------------ *\
            Adds the "Edit Gantt Chart" button on the edit page
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.addEdButton = function (){
        var elTB = document.getElementById('editform');
        if (!elTB)
        {
            jsAlert("Can't place the edit chart button, you probably don't have permissions to edit the page");
            return;
        }

        var nel = document.createElement('a');
        nel.href = "javascript:oJSWikiGanttFrontEnd.startEditor()";
        nel.style.cssText = "float:right";
        nel.style.display = "none";
        nel.id = "editing_button";
        oJSWikiGanttFrontEnd.editBtnRef = nel;
        nel.appendChild(document.createTextNode(this.lang["button label"]));
        elTB.insertBefore(nel, elTB.firstChild);
    }

    /* ------------------------------------------------------------------------ *\
            Called by addEdButton, get's XML code calls parser and list tasks
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.startEditor = function ()
    {
        let strWikicode = oJSWikiGanttFrontEnd.getContents();
        if (!strWikicode) {
            jsAlert('Error parsing XML');
        }
        
        strWikicode = strWikicode.replace(/'/g, '&apos;').replace(/"/g, '&apos;').replace(/&/g, '&amp;');
        if (strWikicode===false)
        {
            jsAlert(this.lang["gantt not found"])
            this.oParent.oListAct.oMsg.close();
        }

        if (!this.parse(strWikicode))
        {
            return;
        }

        // Main editor window: list of tasks
        this.oListAct.show();  
    }
    

    /* ------------------------------------------------------------------------ *\
        @param: arrFields - Objects with attributes for each input field
        @param: strHeader - Title for the form
        @return: html string for the full form with appropriate input fields
        
        Creates the form using array of objects defining fields and title	
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.createForm = function(arrFields, strHeader)
    {
        var strRet = ''
            + '<h2>'+strHeader+'</h2>'
            + '<div style="text-align:left; font-size:12px;" class="msgform">'
        ;
        for (var i=0; i<arrFields.length; i++)
        {
            var oF = arrFields[i];
            if (typeof(oF.value)=='undefined')
            {
                oF.value = '';
            }
            if (typeof(oF.name)=='undefined')
            {
                var now = new Date();
                oF.name = 'undefined_'+now.getTime();
            }

            /* Giving id date to the start and end fields so I can hide them if task is a group task*/
            let className  = (oF.className) ? oF.className : '';

            switch (oF.type)
            {
                default:
                case 'text':
                    var strInpId = oF.id ? oF.id : '';
                    var strExtra = '';
                    strExtra += oF.jsUpdate ? ' onchange="'+oF.jsUpdate+'" ' : '';
                    strExtra += oF.maxlen ? ' maxlength="'+oF.maxlen+'" ' : '';
                    strExtra += oF.maxlen ? ' style="width:'+(oF.maxlen*8)+'px" ' : '';
                    
                    oF.type = oF.type ? oF.type : '';                    
                    strRet += '<p class="'+ className  +'" >'
                        +'<label style="display:inline-block;width:120px;text-align:right;">'+oF.lbl+':</label>'
                        +' <input  id="'+ strInpId +'" class="'+ oF.inputClass +'" type="'+oF.type+'" name="'+oF.name+'" value="'+oF.value+'" '+strExtra+' />'
                        +'</p>'
                    ;
                break;
                case 'checkbox':
                    var strInpId = oF.name;
                    var strExtra = '';
                    var strlbl = '';
                    if(oF.lbl) strlbl = '<label for="'+strInpId+'">'+oF.lbl+':</label>';
                    strExtra += ' onclick=oJSWikiGanttFrontEnd.oModTask.toggleChecked("'+strInpId+'") ';
                    strExtra += oF.jsUpdate ? ' onchange="'+oF.jsUpdate+'" ' : '' ;
                    strExtra += oF.value ? ' checked="checked" ' : '';
                    strRet += '<p class="'+ className +'">'
                        +'<span style="display:inline-block;width:120px;text-align:right;">'+oF.title+':</span>'
                        +' <input id="'+strInpId+'" type="'+oF.type+'" name="'+oF.name+'" value="1" '+strExtra+' />'
                        +strlbl 
                        +'</p>'
                    ;

                break;
                case 'radio':
                    var dt = new Date()
                    var strInpId = oF.name+'_'+dt.getTime();
                    var strExtra = '';
                    strExtra += oF.jsUpdate ? ' onchange="'+oF.jsUpdate+'" ' : '';
                    strRet += '<p class="'+ className +'">'
                        +'<span style="display:inline-block;width:120px;text-align:right;">'+oF.title+':</span>'
                    ;
                    for (var j=0; j<oF.lbls.length; j++)
                    {
                        var oFL = oF.lbls[j];
                        var strSubInpId = strInpId+'_'+oFL.value;
                        var strSubExtra = strExtra;
                        strSubExtra += oF.value==oFL.value ? ' checked="checked" ' : '';
                        strRet += ''
                            +' <input id="'+strSubInpId+'" type="'+oF.type+'" name="'+oF.name+'" value="'+oFL.value+'" '+strSubExtra+' />'
                            +'<label for="'+strSubInpId+'">'+oFL.lbl+'</label>'
                        ;
                    }
                    strRet += '</p>';
                break;
                case 'select':
                    var dt = new Date()
                    var strInpId = oF.name+'_'+dt.getTime();
                    var strExtra = '';
                    strExtra += oF.jsUpdate ? ' onchange="'+oF.jsUpdate+'" ' : '';
                    strRet += '<p class="'+className+'">'
                        +'<span style="display:inline-block;width:120px;text-align:right;">'+oF.title+':</span>'
                        +'<select name="'+oF.name+'" '+strExtra+'>'
                    ;
                    for (var j=0; j<oF.lbls.length; j++)
                    {
                        var oFL = oF.lbls[j];
                        var strSubInpId = strInpId+'_'+oFL.value;
                        var strSubExtra ='';
                        strSubExtra += oF.value==oFL.value ? ' selected="selected" ' : '';
                        strRet += ''
                            +'<option value="'+oFL.value+'" '+strSubExtra+'>'+oFL.lbl+'</option>';
                    }
                    strRet += '</select></p>';
                break;
                case 'default_color_inputs':
                    strRet += '<p class="'+ oF.className +'" style="margin-left:20px;">'
                    + 'Save as Default: <input id="default_color" type="checkbox" onclick=oJSWikiGanttFrontEnd.oModTask.toggleChecked("default_color")>'
                    +  '<button style="margin-left:52px;" type="button" onclick="oJSWikiGanttFrontEnd.oModTask.makeDefaultColor()">Make Default!</button>';
                    + '</p>'
                    
                break;
            }
        }
        
        strRet += '</div>';
        return strRet;
    }

    /* ------------------------------------------------------------------------ *\
        @param: strWikicode - input string to be parsed to XML
        @return: String - parsed XML  
        
        Takes in a string and parses and returns it to XML
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.parseToXMLDoc = function(strWikicode)
    {
        strWikicode = "<root>"+strWikicode+"</root>";
        var docXML;
        if (window.DOMParser)
        {
            var parser = new DOMParser();
            docXML = parser.parseFromString(strWikicode, "text/xml");
        }
        else
        {
            docXML = new ActiveXObject("Microsoft.XMLDOM");
            docXML.async = "false";
            docXML.loadXML(strWikicode);
        }
        return docXML;
    }

    /* ------------------------------------------------------------------------ *\
        @param: strWikiCode - input text from edit area
        @return: boolean - success code for the function 
        
        Called by start editor takes in text from edit area, uses the XML to
        generate the tasks array and set default color from XML
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.parse = function(strWikicode)
    {
        let docXML = this.parseToXMLDoc(strWikicode);
        let elsTasks = docXML.getElementsByTagName('task');

        this.arrTasks = [];
        this.nextId = 1; 
        for (var i=0; i<elsTasks.length; i++)
        {
            var oTask = this.preParseTask(elsTasks[i]);
            if (oTask===false)
            {
                return false;
            }
            this.arrTasks.push(oTask);
        }
        //DEBUG
        console.log('nextId: ' + oJSWikiGanttFrontEnd.nextId);
        
        /* Parse the preferences if any: default color */
        try{
            let prefs = docXML.getElementsByTagName('prefs')[0];
            let defColor = prefs.getElementsByTagName('defcolor')[0].textContent;
            this.conf.defaultColor = defColor
        }catch (e) {}
        
        return true;
    }

    /* ------------------------------------------------------------------------ *\
        @param: nodeTask - XML for a task
        @return: oNewTask - Task object with attributes from XML
        
        Parse helper function, creates an object from XML of a task
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.preParseTask = function(nodeTask)
    {
        let oTask = new Object();
        let strDateStart, intDur, strDateEnd, strColor, strResources, intComp, boolGroup, intParent, intDepend, boolMile;

        /* Handling required fields */
        try
        {
            oTask.intId = parseInt(nodeTask.getElementsByTagName('pID')[0].textContent);
            oTask.strName = nodeTask.getElementsByTagName('pName')[0].textContent;
            
            /* Replace double with single quote otherwise it breaks the gadget sftJSmsg.js */
            oTask.strName = oTask.strName.replace(/"/g, "'");

            /* Trying to get a unique ID */
            if (oTask.intId >= oJSWikiGanttFrontEnd.nextId){
                oJSWikiGanttFrontEnd.nextId = oTask.intId+1;	
            }
        }
        catch (e)
        {
            jsAlert('Can\'t parse tasks.. check tags');
            return false;
        }

        /* Handling optional fields*/
        try{ strDateStart = nodeTask.getElementsByTagName('pStart')[0].textContent;}catch(e){}
        finally{ if (strDateStart){ oTask.strDateStart = strDateStart;} }

        try{ strDateEnd  = nodeTask.getElementsByTagName('pEnd')[0].textContent;} catch(e){} 
        finally{if (strDateEnd){ oTask.strDateEnd = strDateEnd;}}

        try{intDur = parseInt(nodeTask.getElementsByTagName('pDur')[0].textContent);} catch(e){} 
        finally{if (intDur){ oTask.intDur= intDur;}}

        try{strColor = nodeTask.getElementsByTagName('pColor')[0].textContent;} catch(e){} 
        finally{if (strColor){ oTask.strColor = strColor;}}

        try{strResources = nodeTask.getElementsByTagName('pRes')[0].textContent;} catch(e){} 
        finally{if (strResources){ oTask.strResources= strResources;}}

        try{intComp = parseInt(nodeTask.getElementsByTagName('pComp')[0].textContent);} catch(e){} 
        finally{if (intComp){ oTask.intComp = intComp;}
                else{ oTask.intComp = 0;}}

        try{boolGroup = nodeTask.getElementsByTagName('pGroup')[0].textContent;} catch(e){}
        finally{oTask.boolGroup = (boolGroup) ? true : false;}

        try{intParent = parseInt(nodeTask.getElementsByTagName('pParent')[0].textContent);} catch(e){} 
        finally{if (intParent){ oTask.intParent= intParent;}}

        try{intDepend = parseInt(nodeTask.getElementsByTagName('pDepend')[0].textContent);} catch(e){} 
        finally{if (intDepend){ oTask.intDepend= intDepend;}}

        try{boolMile = parseInt(nodeTask.getElementsByTagName('pMile')[0].textContent);} catch(e){} 
        finally{oTask.boolMile = (boolMile) ? true : false;}

        return oTask;
    }

    /* ------------------------------------------------------------------------ *\
        @return: String - Contents in the edit area of the wiki page
        
        Gets the text from edit area. Returns false if it can't find it.
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.getContents = function ()
    {        
        this.elEditArea = document.getElementById('wpTextbox1');
        let el = this.elEditArea;
        let m = el.value.match(this.conf.reGantMatch);
        if (m)
        {
            return m[2];
        }
        return false;
    }

    /* ------------------------------------------------------------------------ *\
        @param: strWikicode - The XML code generated from arrTasks
        
        Sets the contents in <jsgantt> to strWikicode
    \* ------------------------------------------------------------------------ */    
    oJSWikiGanttFrontEnd.setContents = function(strWikicode)
    {
        var el = this.elEditArea;
        el.value = el.value.replace(this.conf.reGantMatch, "$1"+strWikicode+"$3");
    }

    /* ------------------------------------------------------------------------ *\
        @return: strWikicode - XML code of each task from the tasks array
        
        Build the jsGantt XML code by looping through all tasks
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.buildWikicode = function ()
    {
        let strWikicode = '';

        for (var i=0; i<this.arrTasks.length; i++){
            strWikicode += this.buildTaskcode(this.arrTasks[i]);
        }
        
        //Add an XML tag for default color preferences
        strWikicode += '\n'
                     + '<prefs>\n'
                         + '\t<defcolor>' + oJSWikiGanttFrontEnd.conf.defaultColor + '</defcolor>\n'
                     + '</prefs>\n';

        return strWikicode;
    }

    /* ------------------------------------------------------------------------ *\
        @param: oTask - Task object
        @return: String - XML code for the task
        
        Build the jsGantt XML code of a task
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.buildTaskcode = function(oTask)
    {
        let strWikiCode = '';

        let pName = (oTask.strName) 	       ? '\n\t<pName>'+this.encodeHTML(oTask.strName)+'</pName>' : '';
        let pDateStart = (oTask.strDateStart) 	? '\n\t<pStart>'+oTask.strDateStart+'</pStart>' : '';
        let pDateEnd = (oTask.strDateEnd) 	? '\n\t<pEnd>'+oTask.strDateEnd+'</pEnd>' : '';
        let pDur = (oTask.intDur)		? '\n\t<pDur>'+oTask.intDur+'</pDur>' : '';
        let pRes =  (oTask.strResources) 	? '\n\t<pRes>'+this.encodeHTML(oTask.strResources)+'</pRes>' : '';
        let pComp = (oTask.intComp !== null) 		? '\n\t<pComp>'+oTask.intComp+'</pComp>' : '';	
        let pGroup = (oTask.boolGroup) 		? '\n\t<pGroup>1</pGroup>' : '';	
        let pParent = (oTask.intParent) 	? '\n\t<pParent>'+oTask.intParent+'</pParent>' : '';
        let pDepend = (oTask.intDepend) 	? '\n\t<pDepend>'+oTask.intDepend+'</pDepend>' : '';
        let pMile = (oTask.boolMile)		? '\n\t<pMile>1</pMile>' : '';	
        
        try
        {
            strWikiCode = '\n<task>'
                + '\n\t<pID>'+oTask.intId+'</pID>'
                + pName
                + '\n\t<pColor>'+oTask.strColor+'</pColor>'
                + pDateStart
                + pDateEnd
                + pRes
                + pComp
                + pGroup
                + pParent
                + pDepend
                + pMile
                + pDur
                + '\n</task>'
            ;
        }
        //TODO FIX the error
        catch (e)
        {
            jsAlert(this.lang["gantt build error - at task"]
                .replace(/%pID%/g, oTask.intId)
                .replace(/%pName%/g, oTask.strName)
                .replace(/%errDesc%/g, e.description)
            );
            return '';
        }

        return strWikiCode;
    }


    /* ------------------------------------------------------------------------ *\
        Creating the "modify task" object used to modify any task  	
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask = {};


    /* ------------------------------------------------------------------------ *\
        @param: intTaskId - unique id passed in
        
        Display new task template
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.showAdd = function(intTaskId) {
        this.buildLabels(intTaskId);
        let oP = this.oParent;

        let now = new Date();
        oP.oNewTask = {
            intId : intTaskId,
            strName : '',
            strDateStart : now.dateFormat(this.oParent.conf.strFormat),
            strDateEnd : now.dateFormat(this.oParent.conf.strFormat),
            strColor : oP.conf.defaultColor,
            strResources : '',
            intComp : 0,
            boolGroup : false,
            intParent : null, 
            intDepend : null,
            boolMile : false, 
            intDur: 1
        };

        var arrFields = this.getArrFields('oJSWikiGanttFrontEnd.oNewTask');
        var strHTML = this.oParent.createForm(arrFields, this.oParent.lang['header - add']);

        var msg = this.oMsg;
        //msg.saveBtnFunction = 'oJSWikiGanttFrontEnd.oModTask.saveBtnFunction('+ null +', '+ this.oParent.oNewTask.intParent+','+ true +')';
        msg.show(strHTML, 'oJSWikiGanttFrontEnd.oModTask.submitAdd()');
        
        // Create an onclick function for save/exit button
        $('#saveExitBtn').click(function() {
            oJSWikiGanttFrontEnd.oModTask.saveBtnFunction(oJSWikiGanttFrontEnd.oNewTask.intId, oJSWikiGanttFrontEnd.oNewTask.intParent , true); 
        });
        
        msg.repositionMsgCenter();
        $(document).ready(function() {
            jscolor.installByClassName("jscolor");
        });
        
    }

    /* ------------------------------------------------------------------------ *\
        @return: boolean - success code

        Append new task to the list, submit and refresh 
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.submitAdd = function () {
        let oP = this.oParent;
        
        // Make sure next task has a unique id
        oP.nextId++;
        
        if (!(this.preSubmitTask(oP))){
            return false;
        }

        /* Add in new task */
        this.insertTask(null, null);
        oP.oNewTask = null;

        this.submitCommon();
        return true;
    }

    /* ------------------------------------------------------------------------ *\
        @param: taskId - id of the task to edit, embedded in HTML, passed in
        upon click
        
        Gets the object using id and displays it's edit form 	
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.showEdit = function(taskId){

        this.buildLabels(taskId);
        let i;
        for (i=0; i<this.oParent.arrTasks.length; i++){
            let oA = this.oParent.arrTasks[i];

            if (oA.intId === taskId){
                this.oParent.oNewTask = {
                    intId 			: oA.intId,
                    strName 		: oA.strName,
                    strDateStart 	: oA.strDateStart,
                    strDateEnd 		: oA.strDateEnd,
                    intDur 			: oA.intDur,
                    strColor 		: oA.strColor,
                    strResources 	: oA.strResources,
                    intComp 		: oA.intComp,
                    boolGroup 		: oA.boolGroup,
                    intParent 		: oA.intParent, 
                    intDepend 		: oA.intDepend,
                    boolMile 		: oA.boolMile
                };
                break;
            }
        }
        
        //DEBUG
        //console.log('old')
        //console.log(oJSWikiGanttFrontEnd.oNewTask)
        
        let arrFields = this.getArrFields('oJSWikiGanttFrontEnd.oNewTask');
        let strHTML = this.oParent.createForm(arrFields, this.oParent.lang['header - edit']);

        this.oParent.oNewTask.intParent = (this.oParent.oNewTask.intParent) ? this.oParent.oNewTask.intParent : null;

        let msg = this.oMsg;
        msg.show(strHTML, 'oJSWikiGanttFrontEnd.oModTask.submitEdit('+i+', '+ this.oParent.oNewTask.intParent+')');
        
        // Update onclick function for save/exit button
        $('#saveExitBtn').click(function() {
            oJSWikiGanttFrontEnd.oModTask.saveBtnFunction(oJSWikiGanttFrontEnd.oNewTask.intId, oJSWikiGanttFrontEnd.oNewTask.intParent , false); 
        });
        
        //msg.updateSaveBtnFunction();
        msg.repositionMsgCenter();

        /* If group is checked then hide the related fields */
        if (this.oParent.oNewTask.boolGroup){
            let related_fields = document.getElementsByClassName("toggle_visibility_group");
            let i;
            for(i = 0; i < related_fields.length; i++){
                related_fields[i].style.display = "none";
            }
        }
        /* If group is checked then hide the related fields */
        if (this.oParent.oNewTask.boolMile){
            let related_fields = document.getElementsByClassName("toggle_visibility_mile");
            let i;
            for(i = 0; i < related_fields.length; i++){
                related_fields[i].style.display = "none";
            }
        }
        $(document).ready(function() {
            jscolor.installByClassName("jscolor");
        });
    }

    /* ------------------------------------------------------------------------ *\
        @param: taskIndex - previous index of the task edited
        @param: intParentOld - parent id of the task if it had one
        @return: boolean - success code
        
        Assigns the new task object in the array at the same place or finds the 
        new posuition, and moves the children along with it
    \* ------------------------------------------------------------------------ */

    oJSWikiGanttFrontEnd.oModTask.submitEdit = function(taskIndex, intParentOld){

        let oP = this.oParent;
        if (!(this.preSubmitTask(oP))){
            return false;
        }

        /* Removing the old task */
        console.log("1. Removing: " + oP.arrTasks[taskIndex].strName);
        oP.arrTasks.splice(taskIndex, 1);

        /* Add in edited task */
        let movingChildrenInfo = this.insertTask(taskIndex, intParentOld);
        
        /* Move all its children with it*/
        if (movingChildrenInfo) {
            this.moveChildren(movingChildrenInfo.startIndex, movingChildrenInfo.endIndex, movingChildrenInfo.parentNewIndex);
        }
        
        oP.oNewTask = null;

        this.submitCommon();
        return true;
    }

    /* ------------------------------------------------------------------------ *\
        @param: oP - (oJSWikiGanttFrontEnd) passed in to avoid using globals
        @return: boolean - success code
        
        Pre configure task object before submiting (add or edit). Checks and
        assigns all the input to the task object
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.preSubmitTask = function(oP){
        let task = oP.oNewTask;
        /*Debug*/
        //console.log("New task:");
        //console.log(oP.oNewTask);

        /* Parse all integers */    
        task.intComp = (!isNaN(task.intComp) && (task.intComp != null)) ? (parseInt(task.intComp))              : NaN;
        task.intParent = (!isNaN(task.intParent) && (task.intParent != null)) ? (parseInt(task.intParent))      : NaN;
        task.intDepend = (!isNaN(task.intDepend) && (task.intDepend != null)) ? (parseInt(task.intDepend))      : NaN;
        task.intDur = (!isNaN(task.intDur) && (task.intDur != null)) ? (parseInt(task.intDur))                  : NaN;
        
        /* Check for empty string for name */
        if (!task.strName) {
            jsAlert("Task must have a name!");
            return false;
        }
        /* Check date format */
        else if ((!this.isDateFormatCorrect(task.strDateStart)) && (!task.boolGroup)) {
            jsAlert("Please enter the date in 'YYYY-MM-DD' format and make sure it's within bounds");
            return false;
        }
        /* Check duration */
        else if ((isNaN(task.intDur) || task.intDur < 1) && (!task.boolMile && !task.boolGroup)) {
            jsAlert("Duration must be a positive integer");
            return false;
        }
        /* Check for % complete*/
        else if ((isNaN(task.intComp) || task.intComp < 0) && (!task.boolMile && !task.boolGroup)) {
            jsAlert("Percent Complete must be a non-negative integer");
            return false;
        }

        /* Check for hidden fields and set values accordingly */
        if (task.boolMile){
            task.strDateEnd      = task.strDateStart;
            task.intDur          = 1;
        }
        
        /* Deal with parents */
        task.intParent = (task.intParent) ? task.intParent : null;
        
        /* If set default color was checked then change the default color, and uncheck it in conf */
        if (oJSWikiGanttFrontEnd.conf.defaultChecked) {
            oJSWikiGanttFrontEnd.conf.defaultColor = oJSWikiGanttFrontEnd.conf.currentColor;
        }
        oJSWikiGanttFrontEnd.conf.defaultChecked = false;
        
        /* Calculate and set end date if start date and duration is available */
        if (task.strDateStart && (task.intDur > 0 )){
            task.strDateEnd = this.addBusinessDays(task.strDateStart, (task.intDur-1));	
        }
        else if(task.boolGroup === false && task.boolMile === false){
            jsAlert("\"Number of days\" must be a positive integer greater than 0");
            task = null;
            return false;
        }
        
        task = null;
        return true;
    }
    
    /* ------------------------------------------------------------------------ *\
         @param: taskIndex - index or null or position
         @param: intParentOld - tasks previous parent or null
         @param: isNewTask - boolean for calling either submit edit or add  
         
         save and exit button. Calls submitEdit or submitAdd, and submits
         the edit form to return to read page.  	
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.saveBtnFunction = function(taskId, intParentOld, isNewTask){
        
        let submit;
        
        // Submit the task
        if (!isNewTask){
            let taskIndex = this.getTaskIndexbyId(taskId);
            submit = this.submitEdit(taskIndex, intParentOld);
        }
        else{
            submit = this.submitAdd();
        }
        
        // If submission was unsuccessful then return
        if (!submit){
            return;
        }
        // Submit the media wiki form
        $("#editform").submit();
        
        //Close forms
        this.oParent.oListAct.oMsg.close();
        
        //Add semi-transparent overlay while page loads
        this.oParent.createOverlay();
    }
    
    /* ------------------------------------------------------------------------ *\
        @param: taskId - id of task to be deleted. Embedded in HTML, passed
        in upon delete button click
        
        When user clicks on delete task button. generates HTML for the del form
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.showDel = function(taskId){
        let strHTML, i;
        let start = '', end = '';
        for (i=0; i<this.oParent.arrTasks.length; i++){
            let oA = this.oParent.arrTasks[i];
            
            if (oA.intId === taskId){
                let start = (oA.strDateStart) ? ': ' + oA.strDateStart : '';
                let end = (oA.strDateEnd) ? ' - ' + oA.strDateEnd : '';
                
                strHTML = "<h2>"+this.oParent.lang['header - del']+"</h2>" +
                    oA.strName + start + end;	
                break;
            }
        }

        let msg = this.oMsgDel;
        msg.show(strHTML, 'oJSWikiGanttFrontEnd.oModTask.submitDel('+i+')');
        msg.repositionMsgCenter();
    }


    /* ------------------------------------------------------------------------ *\
        @param: taskIndex - index of task to be deleted. Passed in from showDel        
        
        Uses the index of a task object and removes it from the array, and makes 
        it's grandparent adapt the possible orphaned children
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.submitDel = function(taskIndex){

        //Making a reference of the task
        let oTask = this.oParent.arrTasks[taskIndex];
        let arrTasks = this.oParent.arrTasks;
        
        //Remove it from the array
        console.log("2. Removing: " + oJSWikiGanttFrontEnd.arrTasks[taskIndex].strName);
        this.oParent.arrTasks.splice(taskIndex, 1);
        
        // If this task had any children, making their parent null
        let i;
        for (i = 0; i < arrTasks.length; i++) {
            if (arrTasks[i].intParent === oTask.intId) {
                arrTasks[i].intParent = (oTask.intParent) ? oTask.intParent : null;
            }
        }

        this.submitCommon();
    }

    /* ------------------------------------------------------------------------ *\
        @param: taskId - id of the task so we can skip it 
        
        Called from show (add/edit). Builds 2 arrays {label,value} objects
        of all tasks and only groups for the drowndown list (select group/parent)
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.buildLabels = function (taskId)
    {
        this.arrTaskLblsGroup = new Array();
        this.arrTaskLbls = new Array();

        /* Adding an option for none */
        let none_option = {
            value	: null,
            lbl	:  "None"	
        };

        this.arrTaskLblsGroup.push(none_option);	
        this.arrTaskLbls.push(none_option);	

        let i;
        for (i=0; i<this.oParent.arrTasks.length; i++)
        {
            /* Skip itself */
            if (this.oParent.arrTasks[i].intId === taskId) {
                continue;
            }
            
            /* Only use tasks that are grouping tasks */
            if (this.oParent.arrTasks[i].boolGroup) {
                this.arrTaskLblsGroup.push({
                    value	: this.oParent.arrTasks[i].intId,
                    lbl	    : this.oParent.arrTasks[i].strName
                });
            }
            /* Second array containing all tasks */
            this.arrTaskLbls.push({
                value	: this.oParent.arrTasks[i].intId,
                lbl	    : this.oParent.arrTasks[i].strName
            });
        }
    }

    /* ------------------------------------------------------------------------ *\
        @param: strNewTaskObject - input task object
        @return: JSON 
        
        Making the input objects for the task form (edit/add) for task passed in.
        Called by edit/add, output is used to crete
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.getArrFields = function(strNewTaskObject)
    {
        let checkboxValueGroup, checkboxValueMilestone;
        checkboxValueGroup = (this.oParent.oNewTask.boolGroup) ? "checked" : '';
        checkboxValueMilestone = (this.oParent.oNewTask.boolMile) ? "checked" : '';

        return [
            {type:'text',  lbl: 'Title' 
                , value: this.oParent.oNewTask.strName 
                , jsUpdate:strNewTaskObject+'.strName = this.value' },

            {type:'text', maxlen: 10, lbl: 'Start'
                , value: this.oParent.oNewTask.strDateStart
                , jsUpdate:strNewTaskObject+'.strDateStart = this.value'
                , className: "toggle_visibility_group" },

            {type:'text', maxlen: 4, lbl: 'Number of days'
                , value: this.oParent.oNewTask.intDur
                , jsUpdate:strNewTaskObject+'.intDur = this.value'
                , className: "toggle_visibility_group toggle_visibility_mile" },

            {type:'text', lbl: 'Resources'
                , value: this.oParent.oNewTask.strResources 
                , jsUpdate:strNewTaskObject+'.strResources = this.value'
                , className: "toggle_visibility_mile" },

            {type: 'text', maxlen: 3, lbl: 'Complete(%)'
                , value: this.oParent.oNewTask.intComp
                , jsUpdate:strNewTaskObject+'.intComp = this.value' 
                , className: "toggle_visibility_group" },

            {type: 'checkbox', title: 'Group object', name:'group'
                , value: checkboxValueGroup
                , className: "toggle_visibility_mile" },

            {type: 'select', title: 'Parent', lbls: this.arrTaskLblsGroup
                , value: this.oParent.oNewTask.intParent
                , jsUpdate:strNewTaskObject+'.intParent = this.value' },

            {type: 'select', title: 'Depends on', lbls: this.arrTaskLbls
                , value: this.oParent.oNewTask.intDepend
                , jsUpdate:strNewTaskObject+'.intDepend = this.value' },

            {type: 'checkbox', title: 'Milestone', name:'milestone'
                , className: "toggle_visibility_group"
                , value: checkboxValueMilestone },
            
            {type: 'text', lbl: 'Color'
                , value: this.oParent.oNewTask.strColor
                , jsUpdate:strNewTaskObject+'.strColor = this.value'
                , className: "input_color toggle_visibility_group toggle_visibility_mile"
                , inputClass: "jscolor"
                , id: "input_color"},
            
            {type: 'default_color_inputs'
                , className: "toggle_visibility_group toggle_visibility_mile" }
        ];
        
    }

    /* ------------------------------------------------------------------------ *\
        Builds XML code, sets it in the wiki and refreshes all tasks, called
        after any change to tasks
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.submitCommon = function ()
    {
        var strWikicode = this.oParent.buildWikicode();

        this.oParent.setContents(strWikicode);

        this.oMsg.close();

        this.oParent.oListAct.refresh();
    }
    
    /* ------------------------------------------------------------------------ *\
        @param: date - imput date to check if correct format is entered
        
        Regular expression that checks date field format YYYY-MM-DD
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.isDateFormatCorrect = function(date){
        let re = /^(19|20|21)\d{2}-(0[1-9]|1[0-2])-(0[1-9]|1\d|2\d|3[01])/;
        if (re.test(date)) {
            return true;
        }
        return false;
    }

    /* ------------------------------------------------------------------------ *\
        @param: id - group or milestone or default_color. Embedded in HTML
        
        Updates group/milestone of oNewTask and sets visibility of other fields
        depending on group or milestone
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.toggleChecked = function(id){

        let checked = document.getElementById(id).checked; //boolean
        if (id === "group"){
            this.oParent.oNewTask.boolGroup = checked;
            this.updateFieldsVisibility("toggle_visibility_group", checked);
        }
        else if (id === "milestone"){
            this.oParent.oNewTask.boolMile = checked;
            this.updateFieldsVisibility("toggle_visibility_mile", checked);
        }
        else if (id === "default_color") {
            if (checked) {
                oJSWikiGanttFrontEnd.conf.currentColor = $("#input_color")[0].value;
                oJSWikiGanttFrontEnd.conf.defaultChecked = true;
            }
            else {
                oJSWikiGanttFrontEnd.conf.defaultChecked = false;
            }
        }

    }
    
    /* ------------------------------------------------------------------------ *\
        @param: className - class for which to hide or show
        @param: checked - boolean for checkbox, determines hide/show
        
        Helper function deals with setting display of related fields
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.updateFieldsVisibility = function(className, checked){

        /* Toggle visibility for related fields of group task */
        let related_fields = document.getElementsByClassName(className);

        if (checked){
            let i;
            for(i = 0; i < related_fields.length; i++){
                related_fields[i].style.display = "none";
            }
        }
        else {
            let i;
            for(i = 0; i < related_fields.length; i++){
                related_fields[i].style.display = "block";
            }
            /* If we just unchecked milestone and group is still checked */
            if (className === "toggle_visibility_mile" && document.getElementById("group").checked){
                let related_fields_group = document.getElementsByClassName("toggle_visibility_group");
                let i;
                for(i = 0; i < related_fields_group.length; i++){
                    related_fields_group[i].style.display = "none";
                }
            }
        }

    }

    /* ------------------------------------------------------------------------ *\
        @param: startDate - string date in the yyyy-mm-dd format
        @param: days - int number of business days to offset by
        
        Calculates end date given a start date and duration by using moment lib
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.addBusinessDays = function(startDate, days){
        let newDate = new Date(startDate);
        let moment_instance = new moment(startDate, "YYYY-MM-DD");
        
        /* Add business days if moment-business-days-lib is loaded otherwise add normal days */
        let endDate, endDateString;
        if (typeof(moment_instance.businessAdd) === 'function'){
            endDate = moment_instance.businessAdd(days)._d;	
            endDateString = ((endDate.getYear()+1900) + "-" + (endDate.getMonth()+1) + "-" + endDate.getDate());
        }
        else {
            endDate = moment().add(days, 'days'); 
            endDateString = ((endDate._d.getYear()+1900) + "-" + (endDate._d.getMonth()+1) + "-" + endDate._d.getDate());
        }
        return endDateString;	
    }

    /* ------------------------------------------------------------------------------------- *\
        @param: taskIndex - where task used to be or null for new task
        @param: intParentOld - potential parent that the task had before or null
        @return: object or null - { 
                startIndex: starting index of potential children, given index of where task was
                endIndex: index of last child, or null
                parentNewIndex: index of where the task has been inserted, could be the same
                }
        
        Algorithm that finds the proper spot for task in already sorted array and inserts it
        It also accounts for when a  parent task changes then all of it's kids must follow
    \* ------------------------------------------------------------------------------------- */
    oJSWikiGanttFrontEnd.oModTask.insertTask = function (taskIndex, intParentOld){                      // intParentOld may be null
        let oP = this.oParent;
        let oNewTask = oP.oNewTask;
        let len = oP.arrTasks.length;
        let i = 0;
        let stack = []; // Stack is used to keep track of the parent of task i and it's grad-parent(s)
        let task_prev, task_curr, task_next;
        
        /* If array is empty or task didn't change parents */
        if(oP.arrTasks.length === 0 || oNewTask.intParent === intParentOld){
            if(!(isNaN(taskIndex)) && taskIndex != null) {
                oP.arrTasks.splice(taskIndex, 0, oNewTask);
            }
            else{
                oP.arrTasks.push(oNewTask);
            }
            oP.stack = null;
            return null;
        }
        
        let lastChild = this.getLastChild(taskIndex, oNewTask.intId);
        console.log(lastChild);
        
        /* Array is atleast 1 item long and new task has a parent*/
        /* Iterate through the tasks  */
        while (i < len) {
            
            task_prev = oP.arrTasks[i-1];
            task_curr = oP.arrTasks[i];
            task_next = oP.arrTasks[i+1];
            
            /* If it's the last task OR (previous task is not the parent) */
            if (!(task_next) || ((task_prev && task_prev.intId != task_curr.intParent)) ) { 
                
                /* If the task that is being moved has any children, then skip them */
                if (oP.arrTasks[i].intParent === oNewTask.intId) {
                        i++;
                        continue;
                }
                
                /* Keep removing from stack until either (the last element is the parent of current task or stack is empty) */
                while (stack.last() != task_curr.intParent) {                                        
                    let item = stack.pop();
                    
                    /* If we are finished with the subtasks for the desired parent then insert here */
                    if (item === oNewTask.intParent) {
                        oP.arrTasks.splice(i, 0, oNewTask);
                        
                        if (i <= lastChild) {
                            lastChild++;    
                        }
                        
                        if (i <= taskIndex) {
                            taskIndex++;
                        }
                        
                        return {startIndex: taskIndex, endIndex: lastChild, parentNewIndex: i};
                    }
                    
                    /* If stack is empty then desired parent wasn't in there and we can move on */
                    if (stack.length === 0) {
                        break;
                    }
                }
                if (!(task_next)) {
                    oP.arrTasks.push(oNewTask);
                    return {startIndex: taskIndex, endIndex: lastChild, parentNewIndex: oP.arrTasks.length};
                }
            }
            
            /* If grouping task then add to stack */
            if (task_curr.boolGroup) {
                stack.push(task_curr.intId);
            }

            i++;
        }
        
        /* If it exited the loop without inserting the task
        then the last task is it's child, so we keep it at the same spot */
        console.log('Exit array without inserting, so keep it where it was')
        oP.arrTasks.splice(taskIndex, 0, oNewTask);
        return null;
        
    }

    /* ------------------------------------------------------------------------ *\
        @param: whereTaskWas - index of the task (used as starting index of children) 
        @param: parentId - id of parent task
        @return: index of last child of specified task or null
        
        Returns the last child index for a task
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.getLastChild = function(whereTaskWas, parentId) {
        let oP = this.oParent;
        let oNewTask = oP.oNewTask;
        let len = oP.arrTasks.length;
        let i = whereTaskWas;
        let stack = [parentId]; // Stack is used to keep track of the parent of task i and it's grand-parent(s)
        let task_prev, task_curr, task_next;
        
        if (whereTaskWas >= len || !oP.arrTasks[whereTaskWas] || oP.arrTasks[whereTaskWas].intParent !== parentId) {
            return null;
        }
        
        /* Array is atleast 1 item long and new task has a parent*/
        while (i < len) {
            
            task_prev = oP.arrTasks[i-1];
            task_curr = oP.arrTasks[i];
            task_next = oP.arrTasks[i+1];
            
            /* If it's the last task OR (previous task is not the parent) */
            if (!(task_next)  ||  ((task_prev && task_prev.intId != task_curr.intParent)) ) { 
                
                /* Keep removing from stack until either (the last element is the parent of current task or stack is empty) */
                while (stack.last() != task_curr.intParent) {                                        
                    let item = stack.pop();
                    
                    /* If we are finished with the subtasks for the desired parent then insert here */
                    if (item === parentId) {
                        if (oP.arrTasks[i-1].intId !== parentId) {
                            return i-1;
                        }
                        return null;
                    }
                    
                    /* If stack is empty then desired parent wasn't in there and we can move on */
                    if (stack.length === 0) {
                        break;
                    }
                }
                if (!(task_next)) {
                    return i;
                }
            }
            
            /* If grouping task then add to stack */
            if (task_curr.boolGroup) {
                stack.push(task_curr.intId);
            }

            i++;
        }
        return null;
    }
    
    /* ------------------------------------------------------------------------ *\
        @param: startingAt - starting index of subtasks
        @param: endingAt - ending index of subtasks
        @return: parentIndex - index of parent task, for children to be moved under
        
        Moves the children to where the parent moved. Called by dubmit edit
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.moveChildren = function(startingAt, endingAt, parentIndex) {
        let oP = this.oParent;
        let arr = oP.arrTasks;
        let diff = (endingAt != null && startingAt != null) ? endingAt - startingAt : -1;
        let i = 0;
        
        let children = arr.splice(startingAt, diff + 1);
        console.log('children: ' + JSON.stringify(children));
        console.log(startingAt, endingAt, parentIndex);
        
        if (parentIndex > startingAt) {
            parentIndex -= (diff + 1);    
        }
        arr.splice(parentIndex + 1, 0, children)  // since children is also an array we need to flatten the tasks array
        arr = arr.reduce(function(a,b) {
                            return a.concat(b);           
                        }, []);
        oP.arrTasks = arr;        
    }
    
    /* ------------------------------------------------------------------------ *\
        Changes the value of color field to default on button press. Called 
        when make defauilt button is clicked
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oModTask.makeDefaultColor = function() {
        $("#input_color")[0].value = oJSWikiGanttFrontEnd.conf.defaultColor;
        $("#input_color")[0].jscolor.fromString($("#input_color")[0].value)         // update the color immediately
        oJSWikiGanttFrontEnd.oNewTask.strColor = $("#input_color")[0].value; 
    }
    
    
    
    
    oJSWikiGanttFrontEnd.oModTask.getTaskIndexbyId = function(taskId) {
        let i, arrTasks = this.oParent.arrTasks;
        for (i = 0; i < arrTasks.length; i++) {
            if (arrTasks[i].intId === taskId) {
                return i;
            }
        }
        return false;
    }
    
    
    
    
    
    /* ------------------------------------------------------------------------ *\
        @param: st - string to encode as HTML
        @return: string - formatted with special chars escaped

        Escapes the strings with proper sequences
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.encodeHTML = function (st) {
            if (st) {
                return st.replace(/&[^amp]/g, '&amp;')
               .replace(/</g, '&lt;')
               .replace(/>/g, '&gt;')
               .replace(/"/g, '&apos;')
               .replace(/'/g, '&apos;');
            }
            else {
                return ''
            }
    };

    /* ------------------------------------------------------------------------ *\
        List activities object that is used to show tasks
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oListAct = {};


    /* ------------------------------------------------------------------------ *\
        Displays the list of tasks with options to edit or delete. Formatted 
        so tasks with no parents show up with bullet points, and all children
        have been indented according to inheritance
    \* ------------------------------------------------------------------------ */

    oJSWikiGanttFrontEnd.oListAct.show = function () {
        let indentLevel = 0;
        let listStyletype = 'inherit';
        let stack = [];
        let i = 0;
        
        let oP = this.oParent;
        let strList = '<h2>'+ 'Tasks' +'</h2>';
        strList += '<ul style="text-align:left; font-size: 14px;">';

        while (i<oP.arrTasks.length) {
            
            task_prev = oP.arrTasks[i-1];
            task_curr = oP.arrTasks[i];
            task_next = oP.arrTasks[i+1];
            
            if (typeof(task_curr)=='undefined'){
                continue;
            }

            /* If it's not a sub child then decrement indetation until parent is found */
            if (task_prev && task_prev.intId != task_curr.intParent) { 
                /* Increase indent level and reassign prev parent to current one */
                while (stack.last() != task_curr.intParent){
                    let parentFromStack = stack.pop();

                    indentLevel--;
                    
                    if (stack.length === 0) {
                        indentLevel = 0;
                        listStyletype = 'inherit';
                        break;
                    }
                }
            }
            
            else if (task_curr.intParent) {
                indentLevel++;
            }
            if (task_next && task_next.intParent === task_curr.intId){
                stack.push(task_curr.intId);
            }
            if (typeof(task_curr.intParent) === "number" ) {
                listStyletype = 'none';
            }    

            strList += ''
                +'<li style="margin-left:'+ indentLevel * oP.conf.marginSize +'px; list-style:'+ listStyletype +';">'
                    +'<a href="javascript:oJSWikiGanttFrontEnd.oModTask.showEdit('+task_curr.intId+')" title="'
                            +this.oParent.lang["title - edit"]
                        +'">'
                        +task_curr.strName
                    +'</a>'
                    +' '
                    +'<a href="javascript:oJSWikiGanttFrontEnd.oModTask.showDel('+task_curr.intId.toString()+')" title="'
                            +this.oParent.lang["title - del"]
                        +'">'
                        +'<img src="'+this.oParent.conf['img - del']+'" alt="" />'
                    +'</a>'
                +'</li>';
            
            i++;
        }
        strList += ''
            +'<li>'
                +'<a href="javascript:oJSWikiGanttFrontEnd.oModTask.showAdd('+oP.nextId+')" title="'
                            +this.oParent.lang["title - add"]
                        +'">'
                    +this.oParent.lang['label - new activity']
                +'</a>'
            +'</li>';
        strList += '</ul>';


        var msg = this.oMsg;
        msg.show(strList);
        msg.repositionMsgCenter();
    }


    /* ------------------------------------------------------------------------ *\
        Refreshes task list 	
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.oListAct.refresh = function ()
    {
        this.oMsg.close();

        this.show();
    }



    /* ------------------------------------------------------------------------ *\
        Greys out page so user doesn't try to interact with it 	
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.createOverlay = function () {
        let overlay = document.createElement('div');
        overlay.style.backgroundColor = '#e9e9e9';
        //overlay.style.display = 'none';
        overlay.style.position = 'absolute';
        overlay.style.top = 0;
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.opacity = '.7';
        overlay.id = 'gantt_overlay';
        document.body.appendChild(overlay);
    }



    /* ------------------------------------------------------------------------ *\
        Displays Edit Gantt option if the <jsgantt> tags present (keyup event)
        called by init
    \* ------------------------------------------------------------------------ */

    oJSWikiGanttFrontEnd.checkForTags = function () {
        let elEditArea = document.getElementById('wpTextbox1');
        let match = elEditArea.value.match(oJSWikiGanttFrontEnd.conf.reGantMatch);
        if(match){
            let editBtn = oJSWikiGanttFrontEnd.editBtnRef;
            editBtn.style.display = 'block';
            //elEditArea.removeEventListener("keyup", this.checkForTags); Now tag goes away for different editors, so need this
            return true;
        }
        return false;
    }
    

    /* ------------------------------------------------------------------------ *\
        INIT 	
    \* ------------------------------------------------------------------------ */
    oJSWikiGanttFrontEnd.init = function (openTask)
    {
        if (this.conf.strLang in this.lang)
        {
            this.lang = this.lang[this.conf.strLang]
        }
        else
        {
            this.lang = this.lang[this.conf.strFallbackLang]
        }

        // Edit button
        this.addEdButton();
        
        // Check if gantt chart tags are present
        let tagsPresent = this.checkForTags();
        if (!tagsPresent) {
            // Add event listener for the jsgantt tag
            let elEditArea = document.getElementById('wpTextbox1');
            elEditArea.addEventListener("keyup", this.checkForTags);
            
            /** If tags are present but wpTextbox 1 is not because of different editor then
                check again for tags once it becomes visible */
            let observer = new MutationObserver( function(mutations) {
                    mutations.forEach(function(mutation){
                        
                        // Checking if Wiki Editor is active
                        let WikiEditorPresent = false;
                        let editBtn = oJSWikiGanttFrontEnd.editBtnRef;
                        $('#wpTextbox1').attr('style').split(';').forEach(function(element){
                            if ((/display.*inline/).test(element)) {
                                // If wiki text editor && tage present then show edit button
                                if (oJSWikiGanttFrontEnd.checkForTags()){
                                    WikiEditorPresent = true;
                                    editBtn.style.display = 'block';
                                }
                            }
                        });
                        if (!WikiEditorPresent) {
                            // Else remove the button
                            editBtn.style.display = 'none';
                        }

                    });
            });
            
            //Configuring what needs to be observed
            let config = {attributes: true};
            observer.observe(elEditArea, config);
        }
        
        // Task form
        var msg = new sftJSmsg();
        msg.repositionMsgCenter();
        msg.styleWidth = 1000;
        msg.styleZbase += 30;
        msg.showCancel = true;
        msg.showSave = true;
        msg.autoOKClose = false;
        msg.createRegularForm = false;
        this.oModTask.oMsg = msg;
        this.oModTask.oParent = this;
        
        // Task delete form
        var msgDel = new sftJSmsg();
        msgDel.repositionMsgCenter();
        msgDel.styleWidth = 1000;
        msgDel.styleZbase += 40;
        msgDel.showCancel = true;
        msgDel.autoOKClose = true;
        msgDel.createRegularForm = false;
        this.oModTask.oMsgDel = msgDel;

        // Tasks List
        var msg = new sftJSmsg();
        msg.repositionMsgCenter();
        msg.styleWidth = 1000;
        msg.styleZbase += 20;
        msg.showCancel = false;
        msg.lang['OK'] = this.lang["close button label"];
        msg.createRegularForm = false;
        this.oListAct.oMsg = msg;
        this.oListAct.oParent = this;

        // Autoedit
        
        if (location.href.search(/[&?]jsganttautoedit=1/)>=0 && !openTask){
            this.startEditor();
        }

        // If the task was clicked on the Gantt chart
        if (openTask) {
            this.startEditor();
            
            // Find the task requested
            let i = 0, taskRequested;
            for (i; i < this.arrTasks.length; i++) {
                let iTaskName = this.arrTasks[i].strName;
                console.log(iTaskName);
                
                iTaskName = iTaskName.replace(/&quot;|\'|\+|\"/g, '');
                iTaskName = iTaskName.replace(/\s+/g, " ");
                iTaskName = iTaskName.replace(/&amp;/g, "&");
                
                openTask = openTask.replace(/%27/g, "'");
                openTask = openTask.replace(/%3C/g, "<");
                openTask = openTask.replace(/%3E/g, ">");
                if (iTaskName === openTask){
                    taskRequested = this.arrTasks[i];
                    break;
                }
            }
            
            // if found then display it
            if (taskRequested) {
                this.oModTask.showEdit(taskRequested.intId);
            }
            else {
                alert("Task not found...");
            }
        }
    }

    /* ------------------------------------------------------------------------ *\
        Start	
    \* ------------------------------------------------------------------------ */

    
    
    if (window.location.href.indexOf('openTask') > -1) {
        let taskName = window.location.href.split('openTask=')[1];
        window.history.replaceState({}, "", window.location.href.split('#openTask=')[0]);
        
        openTask = taskName.replace(/\+|\'/g, ' ');        
        
        oJSWikiGanttFrontEnd.init(openTask);
    }
    
    else if (mw.config.values.wgAction=="edit" || mw.config.values.wgAction=="submit") {
        oJSWikiGanttFrontEnd.init();
    }



};

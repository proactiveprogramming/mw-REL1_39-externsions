(function (mw) {
    'use strict';

    /**
       * Add AT autocompletion to an input control
       *
       * @param   {string}      id            the ID of the input control
       *
       * @returns string        the autocompletion text
      */
    function addAtAutocomplete(id) {
        var res = $('#' + id).atwho({
            at: '@',
            spaceSelectsMatch: false,
            startWithSpace: false,
            lookUpOnClick: true,
            acceptSpaceBar: true,
            hideWithoutSuffix: false,
            displayTimeout: 300,
            suffix: '',
            limit: 6,
            callbacks: {}
        });

        var values = JSON.parse($('#pchDomains').val());
        for (var key in values) {
            console.log('Adding ' + key + ' = -> ' + JSON.stringify(values[key]));
            res = res.atwho({
                at: key + '=',
                data: values[key]
            });
        }

        return res;
    }

    /**
        * Add Awesome autocompletion to an input control
        *
        * @param   {string}      id            the ID of the input control
        * @param   {string}      type          the type of page
        * @param   {string}      resize        resize the input after initialization?
        *
        * @returns string        the autocompletion text
       */
    function addAWAutocomplete(id, type, resize=false) {
        var ajax = new XMLHttpRequest();
        var p = '*';//$('#params').val();

        ajax.open("GET", mw.config.get('wgScriptPath') + '/api.php?action=smwbrowse&format=json&browse=' + type +
            '&params={"search": "' + p + '","limit": 2000}', true);

        ajax.onload = function () {
            var list = [];
            for (var k in JSON.parse(ajax.responseText).query)
                list.push(k);
            new Awesomplete('#' + id, {
                list: list,

                filter: function (text, input) {
                    return Awesomplete.FILTER_CONTAINS(text, input.match(/[^ ]*$/i)[0]);
                },

                item: function (text, input) {
                    return Awesomplete.ITEM(text, input.match(/[^ ]*$/i)[0]);
                },

                replace: function (text) {
                    var before = this.input.value.match(/^.+\s+|/i)[0];
                    this.input.value = before + text.replace(' ', '_');
                }
            });
        }
        ajax.send();

        if (resize)
            $('#' + id).css('width', '800px');
    }

    addAWAutocomplete('cat', 'category');
    // the textare would be shrinked to a square if not resized afterwards (?)
    addAWAutocomplete('params', 'property', true);
    addAtAutocomplete('params');

}(window.mediaWiki));
(function (mw, $) {

    try {

        /**
         * VectorUp Namespace
         *
         * @type {Object}
         */
        mw.libs.VectorUp = {};

        mw.libs.VectorUp.createGlobalTag = function(propertyName, title) {

            var el = $('[data-property=' + propertyName + ']');

            if (el && el.length > 0 && el.attr('data-value') === 'Ja') {
                url = mw.config.get('wgScript') + '/Spezial:Suche_mittels_Attribut/' + propertyName + '/wahr';
                $('#firstHeading').append('<a class="mobo-tag mobo-tag-' + propertyName + '" href="' + url + '">' + title + '</div>');
            }

        }

        /**
         * Removes rows from tables and creates tags from the values instead that will be appended / prepended to other cells
         *
         * @param  {[type]} tableClass   CSS Class of table to process
         * @param  {[type]} propertyName PropertyName (Uppercase! See HTML)
         * @param  {[type]} name         Human readable name
         * @param  {[type]} cssClass     Add following CSS classes to the tag
         * @param  {[type]} condition    If and condition (string) is given,
         * @param  {[type]} index        Index of the column to insert the tags in (starts with 0)
         * @param  {[type]} prepend      If true the tag will be prepended, if false appended (default)
         */
        mw.libs.VectorUp.createTableTag = function(tableClass, propertyName, name, cssClass, condition, index, prepend) {

            var tableClass = tableClass || 'smwtable';
            var index = index || 0;
            var cssClass = cssClass || 'label label-default'
            var name = name || propertyName;

            $('.' + tableClass + ' th.' + propertyName).hide();
            $('.' + tableClass + ' td.' + propertyName).each(function(i, el) {
                var $el = $(el);

                $el.hide();

                var tag = $('<span> </span><span class="' + cssClass + '">' + name + '</span><span> </span>');

                if (!condition) {
                    name = el.textContent;
                }

                if (condition && (el.textContent !== condition)) {
                    return;
                } else {
                    if (prepend) {
                        $($el.parent()[0].childNodes[index]).prepend(tag);
                    } else {
                        $($el.parent()[0].childNodes[index]).append(tag);
                    }
                }

            });
        }



        // Add View / Formedit button to main hierachy of chameleon menu
        if ($('#ca-nstab-main').length > 0 && !($('body').hasClass('action-view'))) {
            $($('.navbar-collapse > .navbar-nav')[1]).append($('#ca-nstab-main').clone());

            // Add second Save Button in main menu
            if ($("#wpSave").length > 0) {
                var save = $('<li id="sf-save-alt" ><a href="#" title="Änderungen speichern [alt-shift-s]" accesskey="s">Speichern</a></li>')
                $($('.navbar-collapse > .navbar-nav')[1]).append(save);
                save.on('click', function() {
                    $("#wpSave").click();
                });
            }

        }
        if ($('#ca-form_edit').length > 0 && !($('body').hasClass('action-formedit'))) {
            $($('.navbar-collapse > .navbar-nav')[1]).append($('#ca-form_edit').clone());

        }


        // QUICK-FIX: The parent container of a select2 widget is a block div by default.
        // This moves potential info icons down to the next row.
        // To avoid this, make the element an inline-block.
        // Since CSS doesn't support selecting a parent, this has to be done through JavaScript.
        $('sf-select2-container').parent().css({
            'display': 'inline-block'
        });

        // Checklist Hack: If the body contains the word "Checkliste", increase the min-width of the labels
        if (document.body.className.indexOf("Checkliste") > -1) {
            console.log('CHECKLIST!');
            $('#sfForm').addClass('checklistForm');

            $('.formdata td').each(function() {
                $(this).addClass($(this).text().trim());
            });
        }

        // Color booleans
        // $('.smwtable td:contains("falsch")').addClass('td-false');
        // $('.smwtable td:contains("wahr")').addClass('td-true');


        // Auto trims all Input fields
        // TODO: REMOVE console.logs in production
        // mw.hook('sf.formValidationBefore').add(function() {
        //     console.log('sf.formValidationBefore');
        //     var $inputFields = $('#sfForm input');

        //     $inputFields.each(function() {
        //         var val = $(this).val();
        //         var trim = val.trim();
        //         if (val !== trim) {
        //             $(this).val(trim);
        //             console.log('Trimmed "' + val + '" to "' + trim + '"');
        //         }
        //     });
        // });





        // Make specific columns to tags
        // tableClass, propertyName, name, cssClass, condition, index, prepend

        mw.libs.VectorUp.createTableTag('KontaktDatenAsk', 'Bevorzugt', 'bevorzugt', 'label label-warning', 'wahr', 5, true);
        mw.libs.VectorUp.createTableTag('KontaktDatenAsk', 'Privat', 'privat', 'label label-danger', 'wahr', 5, true);
        mw.libs.VectorUp.createTableTag('AdressenAsk', 'Privat', 'privat', 'label label-danger', 'wahr', 0, false);


        // Tag Quality informations
        if ($('.formdata-Qualitaet').length > 0) {
            mw.libs.VectorUp.createGlobalTag('inBearbeitung', 'In Bearbeitung');
            mw.libs.VectorUp.createGlobalTag('falsch', 'Vermutlich falsch');
            mw.libs.VectorUp.createGlobalTag('ungenuegend', 'Ungenügend');
            mw.libs.VectorUp.createGlobalTag('veraltet', 'Veraltet');

            // Free tags (semicolon separated list)
            var tags = $('[data-property=tag]');
            if (tags.length > 0) {
                var val = tags.attr('data-value');

                if (val.trim()) {
                    $.each(val.split(';'), function(index, value) {
                        value = value.trim();
                        url = mw.config.get('wgScript') + '/Spezial:Suche_mittels_Attribut/Tag/' + value;
                        $('#firstHeading').append('<a class="mobo-tag mobo-tag-tag" href="' + url + '">' + value + '</a>');
                    });
                }


            }
        }


        $(document).ready(function() {

            // Hide empty tabs
            var $headertabs = $('.action-view #headertabs')
            if ($headertabs.length > 0) {
                var style = '<style>';

                $headertabs.children().each(function(i, el) {

                    // Hide all Tabs that don't contain data from forms or ask queries
                    if (el.id) {
                        var formContent = $(el).find('.formdata, .smwtable');
                        if (formContent.length === 0) {
                            console.log('Hiding Tab: ' + el.id);
                            style += 'li[aria-controls=' + el.id + '] {display: none;} ';
                        }
                    }

                });
                style += '</style>';
                $('html > head').append($(style));
            }
        });



    } catch (e) {
        console.error('VectorUp.js crashed');
        console.error(e);
    }

}(mediaWiki, jQuery));
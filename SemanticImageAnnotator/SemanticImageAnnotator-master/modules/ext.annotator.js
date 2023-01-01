/**
 * Annotator Extension Main Script
 * Author: DominikMartin, BenjaminHosenfeld, Tobias Weller
 */
// flag representing the status of annotator mode
var loaded = false;
var installed = false;
(function() {
        api.existPage('Form:ImageAnnotation', function(content) {
            if (content) {
                installed = true;
            }
        });

        // append annotate button and status to menu
        if (mw.config.get('wgAction') == 'view') {
            $('#p-views>ul').append('<li id="ca-annotate"><span><a href="#" title="' + mw.msg('sia-button-desc') + '" accesskey="a">' + mw.msg('sia-button-text') + '</a><i class="fa fa-check" aria-hidden="true"></i></span></li>');

            // do if annotate button is clicked
            $('#ca-annotate').click(function() {
                    $('body').append('<div class="annotator-loading"><div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>');
                    loaded = !loaded;

                    if (loaded && installed) {
                        mw.loader.using('ext.imageannotator.module').then(function() {
                            $('#ca-annotate').addClass('selected');
                            // if module is loaded message will pop up
                            mw.notify(mw.message('sia-welcome-message'));
                        });
                    } else if (!installed) {
                        mw.notify(mw.message('sia-please-install'));
                        $('.annotator-loading').hide();
                    } else {
                        //$('#content').annotator('destroy');
                        $('#ca-annotate').removeClass('selected');
                        mw.notify(mw.message('sia-godbye-message'));
                        location.reload();
                    }


            });
        }

}());
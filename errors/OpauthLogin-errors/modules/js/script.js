$(function(){

    var apiUrl = mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?format=json';

    var inputTimeout = null;
    var $input = $('#op_username');
    var $submit = $('#op_username_form input[type="submit"]');

    function displayError() {
        $input.parent('.form-group').addClass('has-error');
        $input.parent().find('.help-block').show();
    }

    function resetError() {
        $input.parent('.form-group').removeClass('has-error');
        $input.parent().find('.help-block').hide();
        $submit.prop('disabled', false);
    }

    function onKeyInput() {
        var value = $input.val().trim();

        // No need for action if there is no value
        if( !value.length || value.length < 3 ) {
            return true;
        }

        // Disable submit
        $submit.prop('disabled', true);

        // Validate entered username
        $.get( apiUrl + '&action=query&list=users&ususers='+value+'&usprop=registration', function(response){
            var users = response.query.users;
            if( users.length ) {
                var user = users[0];
                if (user.hasOwnProperty('missing')) {
                    // No user found
                    resetError();
                }else{
                    // User was found
                    displayError();
                }
            }
        });
    }

    $input.on('keyup', function(e){

        // Prevent submit via enter
        if( e.keyCode === 13 ) {
            e.preventDefault();
            return false;
        }

        if( inputTimeout ) {
            clearTimeout(inputTimeout);
        }
        inputTimeout = setTimeout( onKeyInput, 250 );

    });

    onKeyInput();

});
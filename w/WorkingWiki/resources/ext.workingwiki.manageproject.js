/* WorkingWiki extension for MediaWiki 1.13 and after
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/WorkingWiki
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * JavaScript helper functions for Special:ManageProject page
 */
$.extend( mw.libs.ext.ww, {
	fillAddPage : function () {
		if( !document.getElementById )
			return;
		// Output result
		var pageInput = document.getElementById('ww-add-sf-page');
		if ( pageInput ) {
			var filename = document.getElementById('ww-add-sf-filename').value;
			var projectname = document.getElementById('ww-project').value;
			// TODO: use the project description
			pageInput.value = mw.libs.ext.ww.suggestPage(projectname, null, filename);
		}
	},

	fillAddApfPage : function () {
		if( !document.getElementById )
			return;
		// Output result
		var pageInput = document.getElementById('ww-add-apf-page');
		if ( pageInput ) {
			var filename = document.getElementById('ww-add-apf-filename').value;
			var projectname = document.getElementById('ww-project').value;
			pageInput.value = mw.libs.ext.ww.suggestPage(projectname, null, filename);
		}
	},

	enableProjectOptionsSubmit : function () {
		if( !document.getElementById )
			return;
		var submit = document.getElementById('ww-project-options-submit');
		if (submit)
			submit.disabled=false;
	},

	// attach jQuery handlers for the various events that can
	// happen in the prerequisite-projects form
	bindPrereqEvents : function () {
		// Editing a project's varname or toggling its readonly:
		// enable the update button
		$vn = $( '.ww-manageprerequisites .update-row .varname input' );
		$vn.each( function () {
			$( this ).data( 'original_value', this.value );
		} );
		$vn.off( 'input propertychange' );
		$vn.on( 'input propertychange', function ( e ) {
			if ( e.target.value != $( e.target ).data( 'original_value' ) ) {
				$( e.target ).parents( '.row' )
					.find( '.update' )
					.prop( 'disabled', false );
			}
		} );
		$( '.ww-manageprerequisites .update-row .readonly :input' )
			.off( 'change' )
			.on( 'change', function ( e ) {
				$( e.target ).parents( '.row' )
					.find( '.update' )
					.prop( 'disabled', false );
			} );
		// Editing the project name in the add-new-prerequisite row:
		// turn the add button on or off
		$( '.ww-manageprerequisites .add-row .prerequisite :input' )
			.off( 'input propertychange' )
			.on( 'input propertychange', function ( e ) {
				var present = !!e.target.value;
				$row = $( e.target ).parents( '.row' );
				$row.find( '.add' )
					.prop( 'disabled', !present );
				// TODO: better inference of variable name default
				if ( ! $row.data( 'ww-varname-touched' ) ) {
					var varname = e.target.value
						.replace( /^.*:/, '' )
						.replace( /\.git$/, '' )
						.replace( /\W/g, '' )
						;//.toUpperCase();
					$row.find( '.varname :input' ).val( varname );
				}
			} )
			.trigger( 'input' );
		// When the varname has been edited manually, don't update it automatically
		$( '.ww-manageprerequisites .add-row .varname :input' )
			.off( 'input propertychange' )
			.on( 'input propertychange', function ( e ) {
				$( e.target ).parents( '.row' ).data( 'ww-varname-touched', 1 );
			} );
		// submitting an update: do the operation in place
		var okfn = function ( result, api, opts ) {
			mw.libs.ext.ww.defaultApiOkFn( result, api, opts );
			$( '.ww-dependencies-section' )
				.replaceWith( result[ api['action'] ][ 'html' ] );
			mw.libs.ext.ww.bindPrereqEvents();
		};
		var upfn = function ( event ) {
			// 'ww-action' is set to 'update-prerequisite', which
			// is an action that only works when called CGI-style,
			// i.e. using WWAction.  It reads the button name to
			// decide whether it's a remove or update.  Here we
			// call one or the other directly.
			var api = mw.libs.ext.ww.constructParams( $( event.target ).closest( '.update-row' ) );
			api['action'] = 'ww-set-prerequisite';
			mw.libs.ext.ww.wwlink( event, api, { ok: okfn } );
		};
		$( '.ww-manageprerequisites form.update-row' )
			.off( 'submit' )
			.on( 'submit', upfn );
		$( '.ww-manageprerequisites .update-row input.update' )
			.off( 'click' )
			.on( 'click', upfn );
		// submitting the remove form: do it in place
		var rmfn = function ( event ) {
			var api = mw.libs.ext.ww.constructParams( $( event.target ).closest( '.update-row' ) );
			api['action'] = 'ww-remove-prerequisite';
			mw.libs.ext.ww.wwlink( event, api, { ok : okfn } );
		};
		$( '.ww-manageprerequisites .update-row input.remove' )
			.off( 'click' )
			.on( 'click', rmfn );
		// submitting in the add row: do the operation in place
		var addfn = function ( event ) {
			mw.libs.ext.ww.wwlink(
				event,
				mw.libs.ext.ww.constructParams( $( event.target ).closest( '.add-row' ) ),
				{ ok : okfn }
			);
		};
		$( '.ww-manageprerequisites form.add-row' )
			.off( 'submit' )
			.on( 'submit', addfn );
		$( '.ww-manageprerequisites .add-row input:submit' )
			.off( 'click' )
			.on( 'click', addfn );
	}
} );

mw.libs.ext.ww.bindPrereqEvents();

/**
 * @module popups
 */

import * as Redux from 'redux';
import * as ReduxThunk from 'redux-thunk';

import createPagePreviewGateway from './gateway/page';
import createReferenceGateway from './gateway/reference';
import createUserSettings from './userSettings';
import createPreviewBehavior from './previewBehavior';
import createSettingsDialogRenderer from './ui/settingsDialogRenderer';
import registerChangeListener from './changeListener';
import createIsPagePreviewsEnabled from './isPagePreviewsEnabled';
import { fromElement as titleFromElement } from './title';
import { init as rendererInit, registerPreviewUI, createPagePreview,
	createDisambiguationPreview,
	createReferencePreview
} from './ui/renderer';
import createExperiments from './experiments';
import { isEnabled as isStatsvEnabled } from './instrumentation/statsv';
import changeListeners from './changeListeners';
import * as actions from './actions';
import reducers from './reducers';
import createMediaWikiPopupsObject from './integrations/mwpopups';
import { previewTypes, getPreviewType,
	registerModel,
	isAnythingEligible, findNearestEligibleTarget } from './preview/model';
import isReferencePreviewsEnabled from './isReferencePreviewsEnabled';
import setUserConfigFlags from './setUserConfigFlags';
import { registerGatewayForPreviewType, getGatewayForPreviewType } from './gateway';

const $window = $( window );

const EXCLUDED_LINK_SELECTORS = [
	'.extiw',
	// ignore links that point to the same article
	'.mw-selflink',
	'.image',
	'.new',
	'.internal',
	'.external',
	'.mw-cite-backlink a',
	'.oo-ui-buttonedElement-button',
	'.ve-ce-surface a', // T259889
	'.cancelLink a'
];

/**
 * @typedef {Function} EventTracker
 *
 * An analytics event tracker, i.e. `mw.track`.
 *
 * @param {string} topic
 * @param {Object} data
 *
 * @global
 */

/**
 * Gets the appropriate analytics event tracker for logging metrics to StatsD
 * via [the "StatsD timers and counters" analytics event protocol][0].
 *
 * If logging metrics to StatsD is enabled for the duration of the user's
 * session, then the appriopriate function is `mw.track`; otherwise it's
 * `() => {}`.
 *
 * [0]: https://github.com/wikimedia/mediawiki-extensions-WikimediaEvents/blob/29c864a0/modules/ext.wikimediaEvents.statsd.js
 *
 * @param {Object} user
 * @param {Object} config
 * @param {Experiments} experiments
 * @return {EventTracker}
 */
function getStatsvTracker( user, config, experiments ) {
	return isStatsvEnabled( user, config, experiments ) ? mw.track : () => {};
}

/**
 * Gets the appropriate analytics event tracker for logging virtual pageviews.
 *
 * @param {Object} config
 * @return {EventTracker}
 */
function getPageviewTracker( config ) {
	return config.get( 'wgPopupsVirtualPageViews' ) ? mw.track : () => {
		// NOP
	};
}

/**
 * Subscribes the registered change listeners to the
 * [store](http://redux.js.org/docs/api/Store.html#store).
 *
 * @param {Redux.Store} store
 * @param {Object} registerActions
 * @param {UserSettings} userSettings
 * @param {Function} settingsDialog
 * @param {PreviewBehavior} previewBehavior
 * @param {EventTracker} statsvTracker
 * @param {EventTracker} pageviewTracker
 * @return {void}
 */
function registerChangeListeners(
	store, registerActions, userSettings, settingsDialog, previewBehavior,
	statsvTracker, pageviewTracker
) {
	registerChangeListener( store, changeListeners.footerLink( registerActions ) );
	registerChangeListener( store, changeListeners.linkTitle() );
	registerChangeListener( store, changeListeners.render( previewBehavior ) );
	registerChangeListener(
		store, changeListeners.statsv( registerActions, statsvTracker ) );
	registerChangeListener(
		store, changeListeners.syncUserSettings( userSettings ) );
	registerChangeListener(
		store, changeListeners.settings( registerActions, settingsDialog ) );
	registerChangeListener( store,
		changeListeners.pageviews( registerActions, pageviewTracker )
	);
}

/**
 * Creates an event handler that only executes if the current target
 * is eligible for page previews and a title can be associated with the element.
 *
 * @param {Function} handler
 * @return {Function}
 */
function handleDOMEventIfEligible( handler ) {
	return function ( event ) {
		const target = findNearestEligibleTarget( event.target );
		if ( target === null ) {
			return;
		}
		const mwTitle = titleFromElement( target, mw.config );
		if ( mwTitle ) {
			handler( target, mwTitle, event );
		}
	};
}
/*
 * Initialize the application by:
 * 1. Initializing side-effects and "services"
 * 2. Creating the state store
 * 3. Binding the actions to such store
 * 4. Registering change listeners
 * 5. Triggering the boot action to bootstrap the system
 * 6. When the page content is ready:
 *   - Initializing the renderer
 *   - Binding hover and click events to the eligible links to trigger actions
 */
( function init() {
	setUserConfigFlags( mw.config );

	let compose = Redux.compose;
	const
		// So-called "services".
		generateToken = mw.user.generateRandomSessionId,
		pagePreviewGateway = createPagePreviewGateway( mw.config ),
		referenceGateway = createReferenceGateway(),
		userSettings = createUserSettings( mw.storage ),
		referencePreviewsState = isReferencePreviewsEnabled( mw.user, userSettings, mw.config ),
		settingsDialog = createSettingsDialogRenderer( referencePreviewsState !== null ),
		experiments = createExperiments( mw.experiments ),
		statsvTracker = getStatsvTracker( mw.user, mw.config, experiments ),
		pageviewTracker = getPageviewTracker( mw.config ),
		initiallyEnabled = {
			[ previewTypes.TYPE_PAGE ]:
				createIsPagePreviewsEnabled( mw.user, userSettings, mw.config ),
			[ previewTypes.TYPE_REFERENCE ]: referencePreviewsState
		};

	// If debug mode is enabled, then enable Redux DevTools.
	if ( mw.config.get( 'debug' ) ||
		/* global process */
		process.env.NODE_ENV !== 'production' ) {
		// eslint-disable-next-line no-underscore-dangle
		compose = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;
	}

	const store = Redux.createStore(
		Redux.combineReducers( reducers ),
		compose( Redux.applyMiddleware(
			ReduxThunk.default
		) )
	);
	const boundActions = Redux.bindActionCreators( actions, store.dispatch );
	const previewBehavior = createPreviewBehavior( mw.user, boundActions );

	registerChangeListeners(
		store, boundActions, userSettings, settingsDialog,
		previewBehavior, statsvTracker, pageviewTracker
	);

	boundActions.boot(
		initiallyEnabled,
		mw.user,
		userSettings,
		mw.config,
		window.location.href
	);

	/*
	 * Register external interface exposing popups internals so that other
	 * extensions can query it (T171287)
	 */
	mw.popups = createMediaWikiPopupsObject(
		store, registerModel, registerPreviewUI, registerGatewayForPreviewType
	);

	if ( initiallyEnabled[ previewTypes.TYPE_PAGE ] !== null ) {
		const excludedLinksSelector = EXCLUDED_LINK_SELECTORS.join( ', ' );
		// Register default preview type
		mw.popups.register( {
			type: previewTypes.TYPE_PAGE,
			selector: `#mw-content-text a[href][title]:not(${excludedLinksSelector})`,
			gateway: pagePreviewGateway,
			renderFn: createPagePreview,
			subTypes: [
				{
					type: previewTypes.TYPE_DISAMBIGUATION,
					renderFn: createDisambiguationPreview
				}
			]
		} );
	}
	if ( initiallyEnabled[ previewTypes.TYPE_REFERENCE ] !== null ) {
		// Register the reference preview type
		mw.popups.register( {
			type: previewTypes.TYPE_REFERENCE,
			selector: '#mw-content-text .reference a[ href*="#" ]',
			gateway: referenceGateway,
			renderFn: createReferencePreview
		} );
	}
	if ( !isAnythingEligible() ) {
		mw.log.warn( 'ext.popups was loaded but everything is disabled' );
		return;
	}

	rendererInit();

	/*
	 * Binding hover and click events to the eligible links to trigger actions
	 */
	$( document )
		.on( 'mouseover keyup',
			handleDOMEventIfEligible( function ( target, mwTitle, event ) {
				const $target = $( target );
				const type = getPreviewType( target );
				const gateway = getGatewayForPreviewType( type );
				if ( !gateway ) {
					return;
				}

				const measures = {
					pageX: event.pageX,
					pageY: event.pageY,
					clientY: event.clientY,
					width: $target.width(),
					height: $target.height(),
					offset: $target.offset(),
					clientRects: target.getClientRects(),
					windowWidth: $window.width(),
					windowHeight: $window.height(),
					scrollTop: $window.scrollTop()
				};

				boundActions.linkDwell( mwTitle, target, measures, gateway, generateToken, type );
			} )
		)
		.on( 'mouseout blur',
			handleDOMEventIfEligible( function () {
				boundActions.abandon();
			} )
		)
		.on( 'click',
			handleDOMEventIfEligible( function ( target ) {
				if ( previewTypes.TYPE_PAGE === getPreviewType( target ) ) {
					boundActions.linkClick( target );
				}
			} )
		);
}() );

window.Redux = Redux;
window.ReduxThunk = ReduxThunk;

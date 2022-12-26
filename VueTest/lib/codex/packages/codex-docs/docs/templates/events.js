/** @typedef {import('vue-docgen-api').EventDescriptor} EventDescriptor */

const utils = require( './utils' );
const mdclean = utils.mdclean;

/**
 * Format event properties.
 *
 * @param {EventDescriptor['properties']} properties This event's properties
 * @return {string} Single line of information per event property
 */
function formatProperties( properties ) {
	if ( !properties ) {
		return '';
	}
	return properties
		.map( ( property ) => {
			const { name, description, type } = property;
			if ( !type ) {
				return '';
			}

			const formattedTypes = type.names.map( ( typeName ) => {
				return utils.getTypeText( typeName );
			} );

			// eslint-disable-next-line @typescript-eslint/restrict-template-expressions
			let eventString = `**${name}** ${formattedTypes.join( ', ' )}`;

			// Customization: only show the description if one exists, to avoid appending
			// " - undefined" to the string.
			if ( description ) {
				// eslint-disable-next-line @typescript-eslint/restrict-template-expressions
				eventString += ` - ${description}`;
			}
			return eventString;
		} )
		.join( '\n' );
}

/**
 * Returns a formatted markdown table body with event information.
 *
 * @param {EventDescriptor[]} events This component's events
 * @return {string}
 */
const tmpl = ( events ) => {
	let ret = '';
	events.forEach( ( evt ) => {
		const { description = '', ...e } = evt;
		const readableProperties = e.properties ? `${formatProperties( e.properties )}` : '';
		ret += `| ${mdclean( '`' + e.name + '`' )} | ${mdclean( readableProperties )} | ${mdclean( description )}\n`;
	} );
	return ret;
};

/**
 * Exports a custom template for the events data on a component demo page.
 *
 * Note that most of this code comes directly from vue-docgen-cli, so no improvements have been
 * made—changes to the default code/template have been noted.
 *
 * @type {import('vue-docgen-cli').Templates['events']}
 */
module.exports = function ( events, opt = {} ) {
	return `
${opt.isSubComponent || opt.hasSubComponents ? '#' : ''}## Events

  | Event name     | Properties     | Description  |
  | -------------- |--------------- | -------------|
  ${tmpl( events )}
  `;
};

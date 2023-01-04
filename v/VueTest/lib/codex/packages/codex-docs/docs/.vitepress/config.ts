import { defineConfigWithTheme, DefaultTheme } from 'vitepress';
import { existsSync } from 'fs';
import path from 'path';
import { CustomConfig } from './types';

// If this isn't a tag, branch deploy, or local dev, we're on the main branch. We'll pass this var
// to the theme config so we can use it to display a message.
const isMainBranch = process.env.CODEX_RELEASE === undefined &&
	process.env.CODEX_BRANCH_DEPLOY === undefined &&
	process.env.CODEX_DOC_DEV === undefined;
// If this is a branch deploy, we'll pass this var and the patch ID to the theme config so we can
// show a warning message linking to the patch.
const isBranchDeploy = process.env.CODEX_BRANCH_DEPLOY !== undefined;
const patchID = process.env.CODEX_PATCH_ID;

const includeWIPComponents = process.env.CODEX_RELEASE === undefined;

function isWIPComponent( componentName: string ): boolean {
	return existsSync( path.join( __dirname, '/../../../codex/src/components-wip/', componentName ) );
}

/**
 * In release mode, filter out components that are in development. In
 * non-release mode, don't filter them out, but add a construction emoji
 * "🚧" to their description.
 *
 * @param items Array of sidebar items representing components
 * @return Filtered or modified array of sidebar items
 */
function filterComponents( items: DefaultTheme.SidebarItem[] ): DefaultTheme.SidebarItem[] {
	return items.flatMap( ( item ) => {
		const componentName = ( item.link ?? '' ).match( /^\/components\/demos\/([^/]+)/ )?.[ 1 ];
		if ( componentName && isWIPComponent( componentName ) ) {
			return includeWIPComponents ?
				{ ...item, text: `${item.text} 🚧` } :
				[];
		}
		return item;
	} );
}

export default defineConfigWithTheme<CustomConfig>( {
	lang: 'en-US',
	title: 'Codex',
	description: 'Toolkit for building user interfaces within the Wikimedia Design System',
	base: process.env.CODEX_DOC_ROOT || '/',
	// Disable dark mode.
	appearance: false,

	markdown: {
		theme: 'dracula'
	},

	themeConfig: {
		isMainBranch,
		isBranchDeploy,
		patchID,

		logo: {
			src: '/logo-Wikimedia.svg', alt: 'Wikimedia'
		},

		nav: [
			{ text: 'Using Codex', link: '/using-codex/about', activeMatch: '/using-codex/' },
			{ text: 'Contributing', link: '/contributing/overview', activeMatch: '/contributing/' },
			{ text: 'Design Tokens', link: '/design-tokens/overview', activeMatch: '/design-tokens/' },
			{ text: 'Components', link: '/components/overview', activeMatch: '/components/' },
			{ text: 'Icons', link: '/icons/overview', activeMatch: '/icons/' }
		],

		socialLinks: [
			{ icon: 'github', link: 'https://github.com/wikimedia/design-codex' }
		],

		sidebar: {
			'/using-codex/': [
				{
					text: 'Using Codex',
					items: [
						{ text: 'About Codex', link: '/using-codex/about' }
					]
				},
				/*
				{
					text: 'Designing',
					items: [
						{ text: 'Design principles', link: '/using-codex/design-principles' },
						{ text: 'Design resources', link: '/using-codex/design-resources' },
					]
				},
				*/
				{
					text: 'Developing',
					items: [
						{ text: 'Usage', link: '/using-codex/usage' },
						{ text: 'Packages', link: '/using-codex/packages' }
					]
				},
				{

					text: 'Architecture Decisions',
					items: [
						{ text: 'Overview', link: '/using-codex/adrs/overview' },
						{ text: 'ADR 1 - Design Tokens', link: '/using-codex/adrs/01-adr-design-tokens' },
						{ text: 'ADR 2 - Demo tool', link: '/using-codex/adrs/02-adr-demo-tool' },
						{ text: 'ADR 3 - String types in TypeScript', link: '/using-codex/adrs/03-adr-string-types' },
						{ text: 'ADR 4 - Visual Styles as Less Mixins', link: '/using-codex/adrs/04-adr-less-mixins' }
					]
				}
			],
			'/contributing/': [
				{
					text: 'Contributing',
					items: [
						{ text: 'Overview', link: '/contributing/overview' }
						// eslint-disable-next-line max-len
						// { text: 'Contribution workflow', link: '/contributing/contribution-workflow' }
					]
				},
				{
					text: 'Contributing design',
					items: [
						{ text: 'Designing icons', link: '/contributing/designing-icons' }
					]
				},
				{
					text: 'Contributing code',
					items: [
						{ text: 'Introduction', link: '/contributing/contributing-code/introduction' },
						{ text: 'Developing components', link: '/contributing/contributing-code/developing-components' },
						{ text: 'Testing components', link: '/contributing/contributing-code/testing-components' },
						{ text: 'Component demos', link: '/contributing/contributing-code/component-demos' },
						{ text: 'Working with TypeScript', link: '/contributing/contributing-code/typescript' }
					]
				}
			],
			'/design-tokens/': [
				{
					text: 'Design Tokens',
					items: [
						{ text: 'Overview', link: '/design-tokens/overview' }
					]
				},
				{
					text: '',
					items: [
						{ text: 'Animation', link: '/design-tokens/animation' },
						{ text: 'Border', link: '/design-tokens/border' },
						{ text: 'Box-shadow', link: '/design-tokens/box-shadow' },
						{ text: 'Box-sizing', link: '/design-tokens/box-sizing' },
						{ text: 'Breakpoint', link: '/design-tokens/breakpoint' },
						{ text: 'Color', link: '/design-tokens/color' },
						{ text: 'Cursor', link: '/design-tokens/cursor' },
						{ text: 'Font', link: '/design-tokens/font' },
						{ text: 'Opacity', link: '/design-tokens/opacity' },
						{ text: 'Outline', link: '/design-tokens/outline' },
						{ text: 'Position', link: '/design-tokens/position' },
						{ text: 'Size', link: '/design-tokens/size' },
						{ text: 'Spacing', link: '/design-tokens/spacing' },
						{ text: 'Transition', link: '/design-tokens/transition' },
						{ text: 'Z-Index', link: '/design-tokens/z-index' }
					]
				}
			],
			'/components/': [
				{
					text: 'Components',
					items: [
						{ text: 'Overview', link: '/components/overview' },
						{ text: 'Types and constants', link: '/components/types-and-constants' }
					]
				},
				{
					text: '',
					items: filterComponents( [
						{ text: 'Button', link: '/components/demos/button' },
						{ text: 'ButtonGroup', link: '/components/demos/button-group' },
						{ text: 'Card', link: '/components/demos/card' },
						{ text: 'Checkbox', link: '/components/demos/checkbox' },
						{ text: 'Combobox', link: '/components/demos/combobox' },
						{ text: 'Dialog', link: '/components/demos/dialog' },
						{ text: 'Icon', link: '/components/demos/icon' },
						{ text: 'Link', link: '/components/mixins/link' },
						{ text: 'Lookup', link: '/components/demos/lookup' },
						{ text: 'Menu', link: '/components/demos/menu' },
						{ text: 'MenuItem', link: '/components/demos/menu-item' },
						{ text: 'Message', link: '/components/demos/message' },
						{ text: 'ProgressBar', link: '/components/demos/progress-bar' },
						{ text: 'Radio', link: '/components/demos/radio' },
						{ text: 'SearchInput', link: '/components/demos/search-input' },
						{ text: 'Select', link: '/components/demos/select' },
						{ text: 'Tabs', link: '/components/demos/tabs' },
						{ text: 'Tab', link: '/components/demos/tab' },
						{ text: 'TextInput', link: '/components/demos/text-input' },
						{ text: 'Thumbnail', link: '/components/demos/thumbnail' },
						{ text: 'ToggleButton', link: '/components/demos/toggle-button' },
						{ text: 'ToggleButtonGroup', link: '/components/demos/toggle-button-group' },
						{ text: 'ToggleSwitch', link: '/components/demos/toggle-switch' },
						{ text: 'TypeaheadSearch', link: '/components/demos/typeahead-search' }
					] )
				}
			],
			'/icons/': [
				{
					text: 'Icons',
					items: [
						{ text: 'Overview', link: '/icons/overview' },
						{ text: 'List of all icons', link: '/icons/all-icons' },
						{ text: 'Adding new icons', link: '/icons/adding-new' }
					]
				}
			]
		}
	}
} );

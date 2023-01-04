import { mount } from '@vue/test-utils';
import CdxMenuItem from './MenuItem.vue';
import { MenuItemDataWithId, SearchResultWithId } from '../../types';
import { cdxIconTag } from '@wikimedia/codex-icons';

const testMenuItem = {
	id: 'test-menu-item',
	value: 'menuItemValue'
};
const testMenuItemLabel = 'Test menu item';
const testSearchResult = {
	id: 'test-search-result',
	value: 'searchResultValue',
	description: 'Test search result',
	url: 'https://example.org/test'
};
const testQuery = 'Test search';
const testMenuItemWithLang = {
	id: 'test-menu-item-with-lang',
	value: 'menuItemWithLangValue',
	label: 'Menu item with lang',
	match: '(match)',
	description: 'la descripción en español',
	language: {
		label: 'en',
		match: 'en',
		description: 'es'
	}
};

describe( 'matches the snapshot', () => {
	type Case = [
		msg: string,
		item: MenuItemDataWithId|SearchResultWithId,
		showThumbnail?: boolean,
		searchQuery?: string,

	];

	const cases: Case[] = [
		[ 'Item without label', testMenuItem ],
		[ 'Item with label', { ...testMenuItem, label: testMenuItemLabel } ],
		[ 'Item with match', { ...testMenuItem, match: '(match)' } ],
		[ 'Item with supporting text', { ...testMenuItem, supportingText: '(supporting text)' } ],
		[ 'Item with url', testSearchResult ],
		[ 'Item with icon', { ...testSearchResult, icon: cdxIconTag } ],
		[ 'Item with placeholder thumbnail', testSearchResult, true ],
		[ 'Item with search query', testSearchResult, false, testQuery ],
		[ 'Item with language attributes', testMenuItemWithLang ],
		[ 'Item with language attributes and search query', testMenuItemWithLang, false, 'match' ]
	];

	test.each( cases )(
		'Case %# %s: (%p) => HTML',
		(
			_,
			item,
			showThumbnail = false,
			searchQuery = ''
		) => {
			const wrapper = mount( CdxMenuItem, {
				props: { ...item, showThumbnail, searchQuery }
			} );
			expect( wrapper.element ).toMatchSnapshot();
		} );
} );

// Note that functionality related to being part of a menu (e.g. emitting events related to menu
// state) is tested in the Menu component tests.
describe( 'CdxMenuItem', () => {
	it( 'emits change event on main mouse button mousedown', async () => {
		const wrapper = mount( CdxMenuItem, {
			props: { ...testSearchResult }
		} );
		await wrapper.trigger( 'mousedown' );
		expect( wrapper.emitted( 'change' ) ).toBeTruthy();
		expect( wrapper.emitted( 'change' )?.[ 0 ] ).toEqual( [ 'active', true ] );
	} );
	it( 'does nothing on right click', async () => {
		const wrapper = mount( CdxMenuItem, {
			props: { ...testSearchResult }
		} );
		await wrapper.trigger( 'mousedown', { button: 2 } );
		expect( wrapper.emitted( 'change' ) ).toBeFalsy();
	} );
} );

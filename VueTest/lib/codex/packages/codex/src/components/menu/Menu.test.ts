import { mount, VueWrapper } from '@vue/test-utils';
import { nextTick } from 'vue';
import { CdxMenu, CdxMenuItem } from '../../lib';
import { MenuItemData } from '../../types';

const exampleMenuItems: MenuItemData[] = [
	{ value: 'a', label: 'Option A' },
	{ value: 'b', label: 'Option B' },
	{ value: 'c' },
	{ value: '', label: 'Empty String' },
	{ value: 0, label: 'Number 0' },
	{ value: 'd', label: 'Option D', disabled: true }
];

const defaultProps = {
	menuItems: exampleMenuItems,
	selected: null,
	expanded: true
};

describe( 'Matches the snapshots', () => {
	type Case = [
		msg: string,
		menuItems: MenuItemData[],
		selected: string|number|null,
		expanded?: boolean,
		showPending?: boolean,
		slots?: {
			default?: string,
			'no-results'?: string,
			pending?: string
		}
	];

	const cases: Case[] = [
		[ 'Nothing selected', exampleMenuItems, null ],
		[ 'Something selected', exampleMenuItems, 'b' ],
		[ 'Not expanded', exampleMenuItems, 'b', false ],
		[ 'With no results text', [], null, true, false, { 'no-results': 'No results' } ],
		[ 'Pending', [], null, true, true, { pending: 'Loading...' } ],
		[ 'Pending with items', exampleMenuItems, null, true, true, { pending: 'Loading...' } ],
		[ 'Custom menu item rendering', exampleMenuItems, 'b', true, false, {
			default: `
				<template #default="{ menuItem }">
					{{ menuItem.label }} (value: {{ menuItem.value }})
				</template>
			`
		} ]
	];

	test.each( cases )( 'Case %# %s: => HTML', (
		_, menuItems, selected, expanded = true, showPending = false, slots = {}
	) => {
		const wrapper = mount( CdxMenu, {
			props: { menuItems, selected, expanded, showPending },
			slots
		} );
		expect( wrapper.element ).toMatchSnapshot();
	} );
} );

async function delegateKeydownEvent(
	wrapper: VueWrapper<InstanceType<typeof CdxMenu>>,
	key: string
) {
	const menu = wrapper.vm;
	const handled = menu.delegateKeyNavigation( new KeyboardEvent( 'keydown', { key } ) );
	expect( handled ).toBe( true );
	await nextTick();
}

it( 'Clicking a menu item emits an "update:selected" event with the correct "selectedValue"', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	await wrapper.findAllComponents( CdxMenuItem )[ 0 ].trigger( 'click' );
	expect( wrapper.emitted()[ 'update:selected' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:selected' ][ 0 ] ).toEqual( [ exampleMenuItems[ 0 ].value ] );
} );

it( 'Clicking a menu item with a value of 0 emits an "update:selected" event with the correct "selectedValue"', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		menuItems: exampleMenuItems
	} } );
	await wrapper.findAllComponents( CdxMenuItem )[ 4 ].trigger( 'click' );
	expect( wrapper.emitted()[ 'update:selected' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:selected' ][ 0 ] ).toEqual( [ exampleMenuItems[ 4 ].value ] );
} );

it( 'Clicking a menu item with a value of empty string emits an "update:selected" event with the correct "selectedValue"', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		menuItems: exampleMenuItems
	} } );
	await wrapper.findAllComponents( CdxMenuItem )[ 3 ].trigger( 'click' );
	expect( wrapper.emitted()[ 'update:selected' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:selected' ][ 0 ] ).toEqual( [ exampleMenuItems[ 3 ].value ] );
} );

it( 'Clicking a menu item emits an "update:expanded" event indicating the menu should be closed', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	await wrapper.findAllComponents( CdxMenuItem )[ 0 ].trigger( 'click' );
	expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ false ] );
} );

it( 'Clicking a disabled menu item does not emit any events', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	await wrapper.findAllComponents( CdxMenuItem )[ 5 ].trigger( 'click' );
	expect( wrapper.emitted()[ 'update:modelValue' ] ).toBeFalsy();
	expect( wrapper.emitted()[ 'update:expanded' ] ).toBeFalsy();
} );

it( 'If the selected prop is updated in the parent, the component updates itself to the new value', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'c'
	} } );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 2 ].classes() ).toContain( 'cdx-menu-item--selected' );
	await wrapper.setProps( { selected: 'b' } );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 1 ].classes() ).toContain( 'cdx-menu-item--selected' );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 2 ].classes() ).not.toContain( 'cdx-menu-item--selected' );
} );

it( 'If the selected prop is updated in the parent, no update events are emitted', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'c'
	} } );
	await wrapper.setProps( { modelValue: 'b' } );
	expect( wrapper.emitted()[ 'update:selected' ] ).toBeFalsy();
} );

it( 'Enter keydown expands the menu if it is not already expanded', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		expanded: false
	} } );
	await delegateKeydownEvent( wrapper, 'Enter' );
	expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
} );

it( 'Enter keydown sets the selected menu item to "highlighted" if a selection is present', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'c',
		expanded: false
	} } );
	await delegateKeydownEvent( wrapper, 'Enter' );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 2 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Down arrow keydown sets the next menu item to "highlighted"', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'a',
		expanded: false
	} } );
	// Enter highlights the selected item
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );

	// Pressing ArrowDown highlights the next item
	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 1 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Down arrow keydown opens menu but does not highlight when menu is closed and nothing selected', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		expanded: false
	} } );
	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).not.toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Down arrow keydown opens menu and highlights selected item when menu is closed and selection present', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'c',
		expanded: false
	} } );
	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 2 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Down arrow keydown skips disabled elements and loops around to the beginning if necessary', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 0 // This is the entry before the disabled menuItem
	} } );
	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Up arrow keydown sets the previous menu item to "highlighted"', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'c',
		expanded: false
	} } );
	// Enter highlights the selected item
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );

	// Pressing ArrowUp again highlights the previous item
	await delegateKeydownEvent( wrapper, 'ArrowUp' );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 1 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Up arrow keydown opens menu but does not highlight when menu is closed and nothing selected', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		expanded: false
	} } );
	await delegateKeydownEvent( wrapper, 'ArrowUp' );
	expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).not.toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Up arrow keydown opens menu and highlights selected item when menu is closed and selection present', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'c',
		expanded: false
	} } );
	await delegateKeydownEvent( wrapper, 'ArrowUp' );
	expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
	expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 2 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Up arrow keydown skips disabled elements and loops around to the end if necessary', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'a',
		expanded: false
	} } );
	// Enter highlights the selected item
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );

	await delegateKeydownEvent( wrapper, 'ArrowUp' );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 4 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
} );

describe( 'Home keydown', () => {
	it( 'Sets the first menu item to "highlighted"', async () => {
		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			selected: 'c',
			expanded: false
		} } );
		// Enter highlights the selected item
		await delegateKeydownEvent( wrapper, 'Enter' );
		// Simulate the parent responding to the update:expanded event
		await wrapper.setProps( { expanded: true } );

		// Pressing Home highlight the first item
		await delegateKeydownEvent( wrapper, 'Home' );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
	} );

	it( 'Opens menu but does not highlight when menu is closed and nothing selected', async () => {
		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			expanded: false
		} } );
		await delegateKeydownEvent( wrapper, 'Home' );
		expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
		expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).not.toContain( 'cdx-menu-item--highlighted' );
	} );

	it( 'Opens menu and highlights selected item when menu is closed and selection present', async () => {
		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			selected: 'c',
			expanded: false
		} } );
		await delegateKeydownEvent( wrapper, 'Home' );
		expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
		expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 2 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
	} );

	it( 'Skips disabled elements', async () => {
		// Perform a deep copy and change first entry to disabled
		const updateMenuItems = JSON.parse( JSON.stringify( exampleMenuItems ) );
		updateMenuItems[ 0 ].disabled = true;

		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			menuItems: updateMenuItems,
			selected: 'c',
			expanded: false
		} } );
		// Enter highlights the selected item
		await delegateKeydownEvent( wrapper, 'Enter' );
		// Simulate the parent responding to the update:expanded event
		await wrapper.setProps( { expanded: true } );

		await delegateKeydownEvent( wrapper, 'Home' );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 1 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
	} );
} );

describe( 'End keydown', () => {
	it( 'Sets the last menu item to "highlighted"', async () => {
		// Performs a deep copy and changes the last entry to not be disabled
		const updateMenuItems = JSON.parse( JSON.stringify( exampleMenuItems ) );
		updateMenuItems[ 5 ].disabled = false;

		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			menuItems: updateMenuItems,
			selected: 'c',
			expanded: false
		} } );
		// Enter highlights the selected item
		await delegateKeydownEvent( wrapper, 'Enter' );
		// Simulate the parent responding to the update:expanded event
		await wrapper.setProps( { expanded: true } );

		// Pressing End highlight the last item
		await delegateKeydownEvent( wrapper, 'End' );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 5 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
	} );

	it( 'Opens menu but does not highlight when menu is closed and nothing selected', async () => {
		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			expanded: false
		} } );
		await delegateKeydownEvent( wrapper, 'End' );
		expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
		expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).not.toContain( 'cdx-menu-item--highlighted' );
	} );

	it( 'Opens menu and highlights selected item when menu is closed and selection present', async () => {
		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			selected: 'c',
			expanded: false
		} } );
		await delegateKeydownEvent( wrapper, 'End' );
		expect( wrapper.emitted()[ 'update:expanded' ] ).toBeTruthy();
		expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ true ] );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 2 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
	} );

	it( 'Skips disabled elements', async () => {
		const wrapper = mount( CdxMenu, { props: {
			...defaultProps,
			selected: 'c',
			expanded: false
		} } );
		// Enter highlights the selected item
		await delegateKeydownEvent( wrapper, 'Enter' );
		// Simulate the parent responding to the update:expanded event
		await wrapper.setProps( { expanded: true } );

		await delegateKeydownEvent( wrapper, 'End' );
		expect( wrapper.findAllComponents( CdxMenuItem )[ 4 ].classes() ).toContain( 'cdx-menu-item--highlighted' );
	} );
} );

it( 'Enter keydown after navigating to a new item emits an update event with the value of that item', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'b',
		expanded: false
	} } );
	// Enter opens the menu and highlights the selected item
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );

	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	await delegateKeydownEvent( wrapper, 'Enter' );
	expect( wrapper.emitted()[ 'update:selected' ][ 0 ] ).toEqual( [ exampleMenuItems[ 2 ].value ] );
} );

it( 'Tab key after navigating to a new item emits an update event with the value of that item', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'b',
		expanded: false
	} } );
	// Enter opens the menu and highlights the selected item
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );

	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	await delegateKeydownEvent( wrapper, 'Tab' );
	expect( wrapper.emitted()[ 'update:selected' ][ 0 ] ).toEqual( [ exampleMenuItems[ 2 ].value ] );
} );

it( 'menu-item-keyboard-navigation event is emitted when user highlights an item via keyboard', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'b',
		expanded: false
	} } );
	// Enter highlights the first item
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );

	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	expect(
		wrapper.emitted()[ 'menu-item-keyboard-navigation' ][ 0 ]
	).toMatchObject(
		[ exampleMenuItems[ 2 ] ]
	);
} );

it( 'Highlighted menu item is returned by getHighlightedMenuItem', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		selected: 'b',
		expanded: false
	} } );
	// Enter opens the menu and highlights the selected item
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );
	expect( wrapper.vm.getHighlightedMenuItem() ).toMatchObject( exampleMenuItems[ 1 ] );

	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	expect( wrapper.vm.getHighlightedMenuItem() ).toMatchObject( exampleMenuItems[ 2 ] );
} );

it( 'Esc keydown closes the menu', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	await delegateKeydownEvent( wrapper, 'Escape' );
	expect( wrapper.emitted()[ 'update:expanded' ][ 0 ] ).toEqual( [ false ] );
} );

it( 'Highlight state is not preserved after menu is closed', async () => {
	const wrapper = mount( CdxMenu, { props: {
		...defaultProps,
		expanded: false
	} } );
	// Enter opens the menu; first item is not highlighted.
	await delegateKeydownEvent( wrapper, 'Enter' );
	// Simulate the parent responding to the update:expanded event
	await wrapper.setProps( { expanded: true } );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).not.toContain( 'cdx-menu-item--highlighted' );

	// ArrowDown highlights the first item.
	await delegateKeydownEvent( wrapper, 'ArrowDown' );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).toContain( 'cdx-menu-item--highlighted' );

	// Close the menu
	await wrapper.setProps( { expanded: false } );

	// Reopen the menu
	await delegateKeydownEvent( wrapper, 'Enter' );
	await wrapper.setProps( { expanded: true } );
	expect( wrapper.findAllComponents( CdxMenuItem )[ 0 ].classes() ).not.toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Menu item becomes highlighted on mouseenter and un-highlighted on mouseleave', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];

	await firstMenuItem.trigger( 'mouseenter' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--highlighted' );
	await firstMenuItem.trigger( 'mouseleave' );
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--highlighted' );
} );

it( 'Menu item becomes un-active on mouseleave', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];

	await firstMenuItem.trigger( 'mouseenter' );
	await firstMenuItem.trigger( 'mousedown' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );
	await firstMenuItem.trigger( 'mouseleave' );
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );
} );

it( 'Menu item becomes active on mouseenter -> mousedown', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );

	await firstMenuItem.trigger( 'mouseenter' );
	await firstMenuItem.trigger( 'mousedown' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );
} );

it( 'Menu item is active after mouseenter -> mousedown -> mouseleave -> mouseenter', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );

	await firstMenuItem.trigger( 'mouseenter' );
	await firstMenuItem.trigger( 'mousedown' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );
	await firstMenuItem.trigger( 'mouseleave' );
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );
	await firstMenuItem.trigger( 'mouseenter' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );
} );

it( 'Menu item is not active after mouseenter -> mousedown -> mouseleave -> mouseup -> mouseenter', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps, attachTo: 'body' } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );

	await firstMenuItem.trigger( 'mouseenter' );
	await firstMenuItem.trigger( 'mousedown' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );
	await firstMenuItem.trigger( 'mouseleave' );
	await wrapper.trigger( 'mouseup' );
	await firstMenuItem.trigger( 'mouseenter' );
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );
} );

it( 'Menu item becomes inactive after click', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];
	await firstMenuItem.trigger( 'mouseenter' );
	await firstMenuItem.trigger( 'mousedown' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );

	await firstMenuItem.trigger( 'click' );
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );
} );

it( 'Menu item becomes inactive when clearActive is called', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];
	await firstMenuItem.trigger( 'mouseenter' );
	await firstMenuItem.trigger( 'mousedown' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );

	wrapper.vm.clearActive();
	await nextTick();
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );
} );

it( 'Menu item becomes inactive when another item becomes active', async () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );
	const firstMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 0 ];
	await firstMenuItem.trigger( 'mouseenter' );
	await firstMenuItem.trigger( 'mousedown' );
	expect( firstMenuItem.classes() ).toContain( 'cdx-menu-item--active' );

	const secondMenuItem = wrapper.findAllComponents( CdxMenuItem )[ 1 ];
	await secondMenuItem.trigger( 'mouseenter' );
	await secondMenuItem.trigger( 'mousedown' );
	expect( secondMenuItem.classes() ).toContain( 'cdx-menu-item--active' );
	expect( firstMenuItem.classes() ).not.toContain( 'cdx-menu-item--active' );
} );

it( 'displays no-results slot when there are zero menu items', () => {
	const wrapper = mount( CdxMenu, {
		props: { ...defaultProps, menuItems: [] },
		slots: { 'no-results': 'No results found' }
	} );

	expect( wrapper.vm.computedShowNoResultsSlot ).toBeTruthy();
} );

it( 'hides no-results slot when there are menu items', () => {
	const wrapper = mount( CdxMenu, {
		props: defaultProps,
		slots: { 'no-results': 'No results found' }
	} );

	expect( wrapper.vm.computedShowNoResultsSlot ).toBeFalsy();
} );

it( 'displays no-results slot when there are menu items but showNoResultsSlot prop is set to true', () => {
	const wrapper = mount( CdxMenu, {
		props: { ...defaultProps, showNoResultsSlot: true },
		slots: { 'no-results': 'No results found' }
	} );

	expect( wrapper.vm.computedShowNoResultsSlot ).toBeTruthy();
} );

it( 'hides no-results slot when there are zero menu items but showNoResultsSlot prop is set to false', () => {
	const wrapper = mount( CdxMenu, {
		props: { ...defaultProps, menuItems: [], showNoResultsSlot: false },
		slots: { 'no-results': 'No results found' }
	} );

	expect( wrapper.vm.computedShowNoResultsSlot ).toBeFalsy();
} );

describe( 'delegateKeyNavigation returns true or false correctly', () => {
	const wrapper = mount( CdxMenu, { props: defaultProps } );

	test.each( [ ' ', 'Enter', 'Tab', 'ArrowUp', 'ArrowDown', 'Escape', 'Home', 'End' ] )( 'Returns true for "%s"', ( key ) => {
		expect( wrapper.vm.delegateKeyNavigation( new KeyboardEvent( 'keydown', { key } ) ) ).toBe( true );
	} );

	test.each( [ 'ArrowLeft', 'ArrowRight', 'PageUp', 'PageDown', 'Backspace', 'Delete', 'x', '2' ] )( 'Returns false for "%s"', ( key ) => {
		expect( wrapper.vm.delegateKeyNavigation( new KeyboardEvent( 'keydown', { key } ) ) ).toBe( false );
	} );
} );

import {
	BoundProp,
	SlotValuesWithIcons,
	generateProps,
	generateSlots,
	generateVueTag
} from './codegen';
import { SlotConfigWithValue, PropConfigWithValue } from '../types';

describe( 'codegen', () => {
	it( 'generates props', () => {
		const propValues: Record<string, unknown> = {
			undefVal: undefined,
			nullVal: null,
			trueVal: true,
			falseVal: false,
			stringVal: 'foo',
			intVal: 123,
			objVal: { bar: 'baz' },
			arrVal: [ 'x', 'y', 'z' ]
		};

		// undefVal, nullVal, and falseVal are ignored, trueVal is just present without a
		// value, objVal and arrVal have the values stringified
		const expectedString = 'true-val string-val="foo" :int-val="123" ' +
			':obj-val="{&quot;bar&quot;:&quot;baz&quot;}" ' +
			':arr-val="[&quot;x&quot;,&quot;y&quot;,&quot;z&quot;]"';
		expect( generateProps( propValues ) ).toBe( expectedString );
	} );

	it( 'uses v-bind syntax for BoundProp strings (generateProps)', () => {
		const propValues: Record<string, unknown> = {
			normalString: 'foo',
			boundString: new BoundProp( 'variableName' )
		};

		// normalString with be present without a leading : for the property name, but
		// boundString will have the leading :
		const expectedString = 'normal-string="foo" :bound-string="variableName"';
		expect( generateProps( propValues ) ).toBe( expectedString );
	} );

	it( 'uses v-bind syntax for icon properties (generateVueTag)', () => {
		const defaultControls: Record<string, string|boolean|number> = {
			icon: ''
		};
		const iconChosen: PropConfigWithValue = {
			name: 'icon',
			type: 'icon',
			value: 'cdxIconArrowNext'
		};
		expect(
			generateVueTag( 'cdx-icon', defaultControls, [ iconChosen ], '' )
		).toBe(
			'<cdx-icon :icon="cdxIconArrowNext" />'
		);
	} );

	it( 'generates slots', () => {
		const slotValues: SlotValuesWithIcons = {
			first: 'random content',
			second: 'other content'
		};

		const expectedString = '<template #first>random content</template>' +
			'\n<template #second>other content</template>';
		expect( generateSlots( slotValues ) ).toBe( expectedString );
	} );

	it( 'does not wrapper default slot content in <template>', () => {
		const slotValues: SlotValuesWithIcons = {
			default: 'primary content',
			other: 'other content'
		};

		const expectedString = 'primary content' +
			'\n<template #other>other content</template>';
		expect( generateSlots( slotValues ) ).toBe( expectedString );
	} );

	it( 'allows bypassing escapeHtml() calls for icons', () => {
		// Types are needed because typescript doesn't infer correctly
		const slotText: SlotConfigWithValue = {
			name: 'default',
			type: 'slot',
			value: ''
		};
		const slotIcon: SlotConfigWithValue = {
			name: 'default-icon',
			type: 'slot-icon',
			value: 'cdxIconArrowNext'
		};
		expect(
			generateVueTag( 'cdx-button', {}, [ slotText, slotIcon ], '' )
		).toBe(
			'<cdx-button><cdx-icon :icon="cdxIconArrowNext" /></cdx-button>'
		);
	} );

	it( 'uses escapeHtml() for slot text with icon', () => {
		const slotText: SlotConfigWithValue = {
			name: 'default',
			type: 'slot',
			value: '<p>test</p>'
		};
		const slotIcon: SlotConfigWithValue = {
			name: 'default-icon',
			type: 'slot-icon',
			value: 'cdxIconArrowNext'
		};
		expect(
			generateVueTag( 'cdx-button', {}, [ slotText, slotIcon ], '' )
		).toBe(
			'<cdx-button><cdx-icon :icon="cdxIconArrowNext" /> &lt;p&gt;test&lt;/p&gt;</cdx-button>'
		);
	} );

	it( 'ignores icons for unknown slots', () => {
		const slotIcon: SlotConfigWithValue = {
			name: 'random-icon',
			type: 'slot-icon',
			value: 'cdxIconArrowNext'
		};
		expect(
			generateVueTag( 'cdx-button', {}, [ slotIcon ], '' )
		).toBe(
			'<cdx-button />'
		);
	} );

	it( 'outputs self-closing tag when there are no slots', () => {
		const expectedString = '<cdx-text-input />';
		expect( generateVueTag( 'cdx-text-input', {}, [], '' ) ).toBe( expectedString );
	} );

	it( 'adds v-model when needed', () => {
		const expectedString = '<cdx-toggle-button v-model="buttonValue" />';
		expect(
			generateVueTag( 'cdx-toggle-button', {}, [], 'buttonValue' )
		).toBe( expectedString );
	} );
} );

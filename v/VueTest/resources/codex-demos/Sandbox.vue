<!-- eslint-disable vue/multi-word-component-names -->
<template>
	<div
		:key="dir"
		:dir="dir"
		class="cdx-sandbox"
	>
		<div class="cdx-sandbox__body">
			<main class="cdx-sandbox__content">
				<section id="cdx-button">
					<h2>Button</h2>
					<div>
						<h3>Button Variants</h3>
						<button-variants></button-variants>
						<h3>Button with Icon</h3>
						<button-with-icon></button-with-icon>
						<h3>Quiet Button with Icon</h3>
						<button-icon-only></button-icon-only>
						<h3>Quiet Icon-only Button</h3>
						<button-quiet-icon-only></button-quiet-icon-only>
					</div>
				</section>

				<section id="cdx-button-group">
					<h2>ButtonGroup</h2>
					<div>
						<h3>Button Group With Icon</h3>
						<button-group-with-icons></button-group-with-icons>
						<h3>Button Group With Slot</h3>
						<button-group-with-slot></button-group-with-slot>
					</div>
				</section>

				<section id="cdx-card">
					<h2>Card</h2>
					<div>
						<h3>Card Default</h3>
						<card-default></card-default>
						<h3>Card Maximum</h3>
						<card-maximum></card-maximum>
						<h3>Card With Link</h3>
						<card-with-link></card-with-link>
						<card-with-link-title-only></card-with-link-title-only>
						<h3>Card With Thumbnail</h3>
						<card-with-thumbnail></card-with-thumbnail>
						<card-with-thumbnail-title-only></card-with-thumbnail-title-only>
						<h3>Card With Icon</h3>
						<card-with-icon></card-with-icon>
						<h3>Card Group</h3>
						<card-group-with-thumbnails></card-group-with-thumbnails>
					</div>
				</section>

				<section id="cdx-checkbox">
					<h2>Checkbox</h2>
					<div>
						<h3>Single Checkbox</h3>
						<checkbox-single></checkbox-single>
						<h3>Checkbox Group</h3>
						<checkbox-group></checkbox-group>
						<h3>Inline Checkbox</h3>
						<checkbox-inline></checkbox-inline>
						<h3>Indeterminate Checkbox</h3>
						<checkbox-indeterminate></checkbox-indeterminate>
					</div>
				</section>

				<section id="cdx-combobox">
					<h2>Combobox</h2>
					<div>
						<h3>Basic Combobox</h3>
						<combobox-basic></combobox-basic>
						<h3>Clearable Combobox with start icon</h3>
						<combobox-clearable-start-icon></combobox-clearable-start-icon>
						<h3>Combobox with custom menu item</h3>
						<combobox-custom-menu-item></combobox-custom-menu-item>
						<h3>Combobox with complex menu item</h3>
						<combobox-complex-menu-item></combobox-complex-menu-item>
						<h3>Combobox no results</h3>
						<combobox-no-results></combobox-no-results>
						<h3>Combobox scrolling</h3>
						<combobox-with-scroll></combobox-with-scroll>
						<h3>Disabled Combobox</h3>
						<combobox-disabled></combobox-disabled>
					</div>
				</section>

				<section id="cdx-dialog">
					<h2>Dialog</h2>
					<div>
						<h3>Basic Dialog</h3>
						<dialog-basic></dialog-basic>
						<h3>Max content dialog</h3>
						<dialog-max-content></dialog-max-content>
						<h3>Dialog with a Select component inside</h3>
						<dialog-with-select></dialog-with-select>
						<h3>Dialog with stacked actions</h3>
						<dialog-stacked-actions></dialog-stacked-actions>
					</div>
				</section>

				<section id="cdx-icon">
					<h2>Icon</h2>
					<div>
						<h3>Basic Icon</h3>
						<icon-simple></icon-simple>
					</div>
				</section>

				<section id="cdx-lookup">
					<h2>Lookup</h2>
					<div>
						<h3>Default behavior</h3>
						<lookup-default></lookup-default>
						<h3>Clearable, with start icon</h3>
						<lookup-clearable-start-icon></lookup-clearable-start-icon>
						<h3>No results</h3>
						<lookup-no-results></lookup-no-results>
						<h3>With custom menu item</h3>
						<lookup-custom-menu-item></lookup-custom-menu-item>
						<h3>With fetch</h3>
						<lookup-fetch></lookup-fetch>
						<h3>With placeholder</h3>
						<lookup-placeholder></lookup-placeholder>
					</div>
				</section>

				<section id="cdx-menu">
					<h2>Menu</h2>
					<div>
						<h3>Input with menu</h3>
						<menu-input></menu-input>
						<h3>Input with menu and custom item display</h3>
						<menu-input-custom-item></menu-input-custom-item>
						<h3>Input with menu, no results</h3>
						<menu-input-no-results></menu-input-no-results>
						<h3>Input with menu, pending state</h3>
						<menu-input-pending></menu-input-pending>
						<h3>Input with menu, pending, with items</h3>
						<menu-input-pending-items></menu-input-pending-items>
					</div>
				</section>

				<section id="cdx-menu-item">
					<h2>MenuItem</h2>
					<div>
						<h3>Default Behavior</h3>
						<menu-item-default></menu-item-default>
						<h3>With Description</h3>
						<menu-item-description></menu-item-description>
						<h3>With URL</h3>
						<menu-item-url></menu-item-url>
						<h3>With Thumbnail</h3>
						<menu-item-thumbnail></menu-item-thumbnail>
						<h3>With Icon</h3>
						<menu-item-icon></menu-item-icon>
						<h3>With highlighted query</h3>
						<menu-item-highlight></menu-item-highlight>
						<h3>With match</h3>
						<menu-item-match></menu-item-match>
						<h3>With multiple languages</h3>
						<menu-item-languages></menu-item-languages>
						<h3>With long text</h3>
						<menu-item-long-text></menu-item-long-text>
						<h3>With hidden overflow</h3>
						<menu-item-hidden-overflow></menu-item-hidden-overflow>
					</div>
				</section>

				<section id="cdx-message">
					<h2>Message</h2>
					<div>
						<h3>Fade-in message</h3>
						<message-fade-in></message-fade-in>
						<h3>User-dismissable message</h3>
						<message-user-dismiss></message-user-dismiss>
						<h3>Auto-dismissing message</h3>
						<message-auto-dismiss></message-auto-dismiss>
					</div>
				</section>

				<section id="cdx-radio">
					<h2>Radios</h2>
					<div>
						<h3>Radio group</h3>
						<radio-group></radio-group>
						<h3>Inline Radios</h3>
						<radio-inline></radio-inline>
					</div>
				</section>

				<section id="cdx-search-input">
					<h2>SearchInput</h2>
					<div>
						<h3>Default behavior</h3>
						<search-input-default></search-input-default>
						<h3>With button</h3>
						<search-input-with-button></search-input-with-button>
						<h3>Clearable</h3>
						<search-input-clearable></search-input-clearable>
					</div>
				</section>

				<section id="cdx-select">
					<h2>Select</h2>
					<div>
						<h3>Basic select</h3>
						<select-basic></select-basic>
						<h3>With custom label</h3>
						<select-custom-label></select-custom-label>
						<h3>With complex menu items</h3>
						<select-complex-menu-item></select-complex-menu-item>
						<h3>With custom menu items</h3>
						<select-custom-menu-item></select-custom-menu-item>
						<h3>With scrolling menu</h3>
						<select-with-scroll></select-with-scroll>
					</div>
				</section>

				<section id="cdx-tabs">
					<h2>Tabs</h2>
					<div>
						<h3>Basic Tabs</h3>
						<tabs-basic></tabs-basic>
						<h3>Many Tabs</h3>
						<tabs-many></tabs-many>
						<h3>Dynamically generated tabs</h3>
						<tabs-dynamic></tabs-dynamic>
					</div>
				</section>

				<section id="cdx-text-input">
					<h2>Text Input</h2>
					<div>
						<h3>Default Behavior</h3>
						<text-input-demo></text-input-demo>
					</div>
				</section>

				<section id="cdx-thumbnail">
					<h2>Thumbnails</h2>
					<div>
						<h3>Default Behavior</h3>
						<thumbnail-default></thumbnail-default>
						<h3>With custom icon</h3>
						<thumbnail-custom-icon></thumbnail-custom-icon>
					</div>
				</section>

				<section id="cdx-toggle">
					<h2>Toggle buttons</h2>
					<div>
						<h3>Single toggle button</h3>
						<toggle-button-single></toggle-button-single>
						<h3>Toggle icon button</h3>
						<toggle-icon-button></toggle-icon-button>
						<h3>Toggle icon-only button</h3>
						<toggle-icon-only-button></toggle-icon-only-button>
					</div>
				</section>

				<section id="cdx-toggle-button-group">
					<h2>Toggle button group</h2>
					<div>
						<h3>Single Value</h3>
						<toggle-button-group-single-value></toggle-button-group-single-value>
						<h3>Single Value, with initial selection</h3>
						<toggle-button-group-initial-selection></toggle-button-group-initial-selection>
						<h3>Multiple Value</h3>
						<toggle-button-group-multi-value></toggle-button-group-multi-value>
						<h3>Disabled</h3>
						<toggle-button-group-disabled></toggle-button-group-disabled>
						<h3>Maximum</h3>
						<toggle-button-group-maximum></toggle-button-group-maximum>
						<h3>With slot</h3>
						<toggle-button-group-with-slot></toggle-button-group-with-slot>
					</div>
				</section>

				<section id="cdx-toggle-switch">
					<h2>Toggle Switch</h2>
					<div>
						<h3>Single switch</h3>
						<toggle-switch-single></toggle-switch-single>
						<h3>Single switch with label</h3>
						<toggle-switch-with-label></toggle-switch-with-label>
					</div>
				</section>

				<section id="cdx-typeahead-search">
					<h2>TypeaheadSearch</h2>
					<div>
						<h3>TypeaheadSearch Wikipedia</h3>
						<typeahead-search-wikipedia></typeahead-search-wikipedia>
						<h3>TypeaheadSearch Wikidata</h3>
						<typeahead-search-wikidata></typeahead-search-wikidata>
						<h3>TypeaheadSearch with initial value</h3>
						<typeahead-search-initial-value></typeahead-search-initial-value>
						<h3>TypeaheadSearch with pending state</h3>
						<typeahead-search-pending-state></typeahead-search-pending-state>
						<h3>TypeaheadSearch no results</h3>
						<typeahead-search-no-results></typeahead-search-no-results>
					</div>
				</section>
			</main>

			<nav class="cdx-sandbox__nav">
				<div class="cdx-sandbox__nav__inner">
					<ul>
						<li v-for="( section, index ) in demoSections" :key="index">
							<a :href="section.id">
								{{ section.name }}
							</a>
						</li>
					</ul>
				</div>
			</nav>
		</div>
	</div>
</template>

<script>
const Demos = require( './codex-demos.cjs.js' );
const demoSections = [
	{ name: 'Button', id: '#cdx-button' },
	{ name: 'ButtonGroup', id: '#cdx-button-group' },
	{ name: 'Card', id: '#cdx-card' },
	{ name: 'CheckBox', id: '#cdx-checkbox' },
	{ name: 'Combobox', id: '#cdx-combobox' },
	{ name: 'Dialog', id: '#cdx-dialog' },
	{ name: 'Icon', id: '#cdx-icon' },
	{ name: 'Lookup', id: '#cdx-lookup' },
	{ name: 'Menu', id: '#cdx-menu' },
	{ name: 'MenuItem', id: '#cdx-menu-item' },
	{ name: 'Message', id: '#cdx-message' },
	{ name: 'ProgressBar', id: '#cdx-progress-bar' },
	{ name: 'Radio', id: '#cdx-radio' },
	{ name: 'SearchInput', id: '#cdx-search-input' },
	{ name: 'Select', id: '#cdx-select' },
	{ name: 'Tabs', id: '#cdx-tabs' },
	{ name: 'TextInput', id: '#cdx-text-input' },
	{ name: 'Thumbnail', id: '#cdx-thumbnail' },
	{ name: 'Toggle', id: '#cdx-toggle' },
	{ name: 'ToggleButtonGroup', id: '#cdx-toggle-button-group' },
	{ name: 'TypeaheadSearch', id: '#cdx-typeahead-search' }
];

// @vue/component
module.exports = {
	components: {
		ButtonVariants: Demos.ButtonVariants,
		ButtonWithIcon: Demos.ButtonWithIcon,
		ButtonIconOnly: Demos.IconOnlyButton,
		// eslint-disable-next-line vue/no-unused-components
		ButtonQuietWithIcon: Demos.QuietButtonWithIcon,
		ButtonQuietIconOnly: Demos.QuietIconOnlyButton,
		ButtonGroupWithIcons: Demos.ButtonGroupWithIcons,
		ButtonGroupWithSlot: Demos.ButtonGroupWithSlot,
		CardDefault: Demos.CardDefault,
		CardWithIcon: Demos.CardWithIcon,
		CardMaximum: Demos.CardMaximum,
		CardWithLink: Demos.CardWithLink,
		CardWithLinkTitleOnly: Demos.CardWithLinkTitleOnly,
		CardWithThumbnail: Demos.CardWithThumbnail,
		CardWithThumbnailTitleOnly: Demos.CardWithThumbnailTitleOnly,
		CardGroupWithThumbnails: Demos.CardGroupWithThumbnails,
		CheckboxSingle: Demos.SingleCheckbox,
		CheckboxGroup: Demos.CheckboxGroup,
		CheckboxInline: Demos.InlineCheckboxes,
		CheckboxIndeterminate: Demos.IndeterminateState,
		ComboboxBasic: Demos.ComboboxBasic,
		ComboboxClearableStartIcon: Demos.ComboboxClearableStartIcon,
		ComboboxComplexMenuItem: Demos.ComboboxComplexMenuItem,
		ComboboxCustomMenuItem: Demos.ComboboxCustomMenuItem,
		ComboboxNoResults: Demos.ComboboxNoResults,
		ComboboxWithScroll: Demos.ComboboxWithScroll,
		ComboboxDisabled: Demos.ComboboxDisabled,
		DialogBasic: Demos.DialogBasic,
		DialogMaxContent: Demos.DialogMaxContent,
		DialogWithSelect: Demos.DialogWithSelect,
		DialogStackedActions: Demos.DialogStackedActions,
		IconSimple: Demos.SimpleIcon,
		LookupDefault: Demos.LookupDefault,
		LookupCustomMenuItem: Demos.LookupWithCustomMenuItem,
		LookupNoResults: Demos.LookupNoResults,
		LookupFetch: Demos.LookupWithFetch,
		LookupClearableStartIcon: Demos.LookupClearableStartIcon,
		LookupPlaceholder: Demos.LookupWithPlaceholder,
		MenuInput: Demos.InputWithMenu,
		MenuInputCustomItem: Demos.InputWithMenuCustomItemDisplay,
		// eslint-disable-next-line vue/no-unused-components
		MenuInputScroll: Demos.InputWithMenuScroll,
		MenuInputNoResults: Demos.InputWithMenuNoResults,
		MenuInputPending: Demos.InputWithMenuPending,
		MenuInputPendingItems: Demos.InputWithMenuPendingWithItems,
		MenuItemDefault: Demos.MenuItemDefault,
		MenuItemDescription: Demos.MenuItemWithDescription,
		MenuItemUrl: Demos.MenuItemWithUrl,
		MenuItemIcon: Demos.MenuItemWithIcon,
		MenuItemHighlight: Demos.MenuItemHighlightQuery,
		MenuItemThumbnail: Demos.MenuItemThumbnail,
		MenuItemMatch: Demos.MenuItemWithMatch,
		MenuItemLongText: Demos.MenuItemLongText,
		MenuItemHiddenOverflow: Demos.MenuItemHideOverflow,
		// eslint-disable-next-line vue/no-unused-components
		MenuItems: Demos.MenuItems,
		// eslint-disable-next-line vue/no-unused-components
		MenuItemsGraphemes: Demos.MenuItemsGraphemes,
		MenuItemLanguages: Demos.MenuItemMultipleLangs,
		MessageFadeIn: Demos.MessageFadeIn,
		MessageUserDismiss: Demos.MessageUserDismiss,
		MessageAutoDismiss: Demos.MessageAutoDismiss,
		RadioGroup: Demos.RadioGroup,
		RadioInline: Demos.InlineRadios,
		SearchInputDefault: Demos.SearchInputDefault,
		SearchInputWithButton: Demos.SearchInputWithButton,
		SearchInputClearable: Demos.SearchInputClearable,
		SelectBasic: Demos.BasicSelect,
		SelectCustomLabel: Demos.SelectCustomLabel,
		SelectComplexMenuItem: Demos.SelectComplexMenuItem,
		SelectCustomMenuItem: Demos.SelectCustomMenuItem,
		SelectWithScroll: Demos.SelectWithScroll,
		TabsBasic: Demos.BasicTabs,
		TabsMany: Demos.ManyTabs,
		TabsDynamic: Demos.DynamicallyGeneratedTabs,
		TextInputDemo: Demos.TextInputDemo,
		ThumbnailDefault: Demos.ThumbnailDefault,
		ThumbnailCustomIcon: Demos.ThumbnailCustomIcon,
		ToggleButtonSingle: Demos.ToggleSingleButton,
		ToggleIconButton: Demos.ToggleIconButton,
		ToggleIconOnlyButton: Demos.ToggleIconOnlyButton,
		ToggleButtonGroupSingleValue: Demos.SingleValueToggleButtonGroup,
		ToggleButtonGroupInitialSelection: Demos.InitiallySelectedSingleValueToggleButtonGroup,
		ToggleButtonGroupMultiValue: Demos.MultiValueToggleButtonGroup,
		ToggleButtonGroupDisabled: Demos.DisabledButtonGroup,
		ToggleButtonGroupMaximum: Demos.MaximumToggleButtonGroup,
		ToggleButtonGroupWithSlot: Demos.ToggleButtonGroupWithSlot,
		ToggleSwitchSingle: Demos.SingleSwitch,
		ToggleSwitchWithLabel: Demos.SingleSwitchWithLabel,
		TypeaheadSearchWikipedia: Demos.TypeaheadSearchWikipedia,
		TypeaheadSearchWikidata: Demos.TypeaheadSearchWikidata,
		TypeaheadSearchInitialValue: Demos.TypeaheadSearchInitialValue,
		TypeaheadSearchPendingState: Demos.TypeaheadSearchPendingState,
		TypeaheadSearchNoResults: Demos.TypeaheadSearchNoResult

	},
	setup() {
		return {
			demoSections
		};
	}
};

</script>

<style lang="less">
// TODO import Codex tokens, not available in NPM yet
@spacing-large: 1em;
@spacing-xx-large: 2em;
@font-family-base: sans-serif;
@background-color-base: #fff;
@border-base: 1px solid #a2a9b1;
@line-height-100: 1.6;
@z-index-1: 1;

// Define some sandbox-specific values
@sandbox-breakpoint-tablet: 768px;
@sandbox-header-height: 2em;
@sandbox-sidebar-width: 20rem;

@sandbox-scroll-padding: @sandbox-header-height + ( @spacing-large *2 ) + @spacing-large;

html {
	scroll-behavior: smooth;
	scroll-padding-top: @sandbox-scroll-padding;
}

.cdx-sandbox {
	display: flex;
	flex-direction: column;
	font-family: @font-family-base;

	&__header {
		background-color: @background-color-base;
		display: flex;
		align-items: center;
		justify-content: space-between;
		position: sticky;
		top: 0;
		z-index: @z-index-1;
		height: @sandbox-header-height;
		border-bottom: @border-base;
		padding: @spacing-large;

		h1 {
			display: inline-flex;
			margin-top: 0;
			margin-bottom: 0;
			border-bottom: 0;
		}
	}

	&__body {
		display: flex;
		flex: 1;
		flex-direction: column;
		justify-content: space-between;
		padding: @spacing-large;

		@media ( min-width: @sandbox-breakpoint-tablet ) {
			flex-direction: row;
		}

		section {
			margin-bottom: @spacing-xx-large;
			padding-bottom: @spacing-xx-large;
			border-bottom: @border-base;

			h2 {
				margin-top: 0;
				border-bottom: 0;
			}

			h3 {
				margin-bottom: 0.25em;
			}

			// Add some space between demos for the same component.
			h3:not( :first-child ) {
				margin-top: 1em;
			}
		}
	}

	&__content {
		flex: 1;
		// Default min-width of a flex item is `auto`, which is making it take up too much space.
		// Change to 0 instead, which will make it fit within the container along with the nav.
		min-width: 0;
	}

	&__nav {
		flex: 1;
		order: -1;
		margin-bottom: @spacing-xx-large;

		@media ( min-width: @sandbox-breakpoint-tablet ) {
			flex: 0 0 auto;
			flex-direction: row;
			padding-right: 3em;
		}

		&__inner {
			position: sticky;
			top: @sandbox-scroll-padding;

			ul {
				list-style-type: none;
				margin: 0;
				padding: 0;
				line-height: @line-height-100;
			}
		}
	}
}
</style>

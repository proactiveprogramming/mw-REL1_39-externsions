/*!
 * This file contains all types, both internal ones and externally exported ones.
 *
 * Exported types should be marked with `@public` comments in this file, AND explicitly
 * exported in lib.ts.
 */

import { Icon } from '@wikimedia/codex-icons';
import {
	ButtonActions,
	ButtonTypes,
	MessageTypes,
	TextInputTypes,
	ValidationStatusTypes,
	MenuStates
} from './constants';

/** @public */
export type HTMLDirection = 'ltr' | 'rtl';

/** @public */
export type ButtonAction = typeof ButtonActions[ number ];
/** @public */
export type ButtonType = typeof ButtonTypes[ number ];

/** @public */
export interface ButtonGroupItem {
	/** Button value or unique identifier */
	value: string | number,
	/**
	 * Display label for the button, or null to show no label (for icon-only buttons).
	 * If the label property is missing, the value property is used as the label.
	 */
	label?: string|null,
	/** Icon to display before the label */
	icon?: Icon,
	/** ARIA label for the button. Used for accessibility for icon-only buttons. */
	ariaLabel?: string,
	/** Whether the button is disabled */
	disabled?: boolean
}

/** @public */
export type MessageType = typeof MessageTypes[ number ];
export type MessageIconMap = {
	[P in MessageType]: Icon
}

/** @public */
export type TextInputType = typeof TextInputTypes[ number ];

/** @public */
export type ValidationStatusType = typeof ValidationStatusTypes[ number ];

/** @public */
export interface Thumbnail {
	url: string;
	/** Image width in pixels. */
	width?: number | null;
	/** Image height in pixels. */
	height?: number | null;
}

/** @public */
export interface MenuItemLanguageData {
	/** lang attribute of the label. */
	label?: string,
	/** lang attribute of the match. */
	match?: string,
	/** lang attribute of the supporting text. */
	supportingText?: string,
	/** lang attribute of the description. */
	description?: string
}

/** @public */
export interface MenuItemData {
	/** Item value or unique identifier. */
	value: string | number,
	/** Display label for the menu item. */
	label?: string,
	/** Text to be appended to the result's label, e.g. text matching a search query. */
	match?: string,
	/** Text to display next to to the item's label. */
	supportingText?: string,
	description?: string | null,
	/** Lang attributes of text properties. */
	language?: MenuItemLanguageData,
	icon?: Icon,
	thumbnail?: Thumbnail | null,
	/** If URL is included, menu item will be wrapped in an anchor element. */
	url?: string,
	disabled?: boolean
}

/** @public */
export interface MenuItemDataWithId extends MenuItemData {
	id: string
}

/** @public */
export type MenuState = typeof MenuStates[ number ];

/** @public */
export interface MenuConfig {
	/** The maximum number of items visible in an expanded menu */
	visibleItemLimit?: number | null
	/** Whether to show thumbnails (or placeholder). */
	showThumbnail?: boolean,
	/** Whether to bold menu item labels. */
	boldLabel?: boolean,
	/** Whether to hide description text overflow via an ellipsis. */
	hideDescriptionOverflow?: boolean
}

/** @public */
export interface SearchResult extends MenuItemData {
	/** Result link. */
	url: string
}

export type SearchResultWithId = SearchResult & MenuItemDataWithId;

/** @public */
export interface SearchResultClickEvent {
	/** Search result that was clicked. */
	searchResult: SearchResult|null,
	/** Index of the search result within the array of results. */
	index: number,
	/** Number of search results. */
	numberOfResults: number
}

export type StringTypeValidator<T extends string> = ( s: unknown ) => s is T;

export interface TabData {
	name: string,
	label: string,
	id: string,
	disabled: boolean
}

/** @public */
export interface DialogAction {
	label: string,
	disabled?: boolean
}

/** @public */
export interface PrimaryDialogAction extends DialogAction {
	actionType: 'progressive' | 'destructive'
}

/** @public */
export interface BoxDimensions {
	width: number|undefined,
	height: number|undefined
}

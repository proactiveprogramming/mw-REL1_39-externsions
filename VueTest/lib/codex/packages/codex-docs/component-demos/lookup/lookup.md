<script setup>
import LookupDefault from '@/../component-demos/lookup/examples/LookupDefault.vue';
import LookupWithCustomMenuItem from '@/../component-demos/lookup/examples/LookupWithCustomMenuItem.vue';
import LookupNoResults from '@/../component-demos/lookup/examples/LookupNoResults.vue';
import LookupWithFetch from '@/../component-demos/lookup/examples/LookupWithFetch.vue';
import LookupClearableStartIcon from '@/../component-demos/lookup/examples/LookupClearableStartIcon.vue';
import LookupWithPlaceholder from '@/../component-demos/lookup/examples/LookupWithPlaceholder.vue';
</script>

::: tip TextInput props apply
This component contains a TextInput component. You can bind [TextInput props](./text-input.html#usage)
to this component and they will be passed to the TextInput within.
:::

::: tip Attributes passed to input
This component will pass any HTML attributes applied to it, except for CSS class, to the `<input>`
element within the component.
:::

## Demos

### Default

The Lookup component will emit `input` events on input, which the parent component should
react to by computing or fetching menu items, then providing those items to the Lookup component for
display by updating the `menu-items` prop.

Items are displayed via the MenuItem component—see the [MenuItem docs](./menu-item) for more
options. In this example, a `menuConfig` object is passed to the Lookup to bold the label text.

Note that in this example, menu items are Wikidata items with a human-readable label and a Wikidata
entity ID value.

::: warning
The parent component must update the `menu-items` prop after each `input` event, otherwise
the Lookup component will stay in the pending state indefinitely. If there are no results for the
given input, set the `menu-items` prop to an empty array (`[]`).
:::

<cdx-demo-wrapper :force-controls="true">
<template v-slot:demo>
<lookup-default />
</template>
<template v-slot:code>

<<< @/../component-demos/lookup/examples/LookupDefault.vue

</template>
</cdx-demo-wrapper>

### With custom menu item display

The `menu-item` slot can be used to set up custom menu item content and formatting.

<cdx-demo-wrapper>
<template v-slot:demo>
<lookup-with-custom-menu-item />
</template>
<template v-slot:code>

<<< @/../component-demos/lookup/examples/LookupWithCustomMenuItem.vue

</template>
</cdx-demo-wrapper>

### With "no results" content

A non-interactive "no results" message can be displayed via the `no-results` slot. If populated,
this slot will automatically display when there are zero menu items.

<cdx-demo-wrapper>
<template v-slot:demo>
<lookup-no-results />
</template>
<template v-slot:code>

<<< @/../component-demos/lookup/examples/LookupNoResults.vue

</template>
</cdx-demo-wrapper>

### With fetched results and infinite scroll

Often, a Lookup component is used to fetch results from an API endpoint. Parent components should
react to the `input` event emitted by Lookup to search for results, then pass back to the
Lookup either an array of results to display as menu items or an empty array if there are no
results. Between those two events, a pending state animation will display in the input.

Scrolling is enabled by setting the `visibleItemLimit` property of the `menuConfig` prop.

With scrolling enabled, when the user nears the end of the list of results, a `load-more` event is
emitted. The parent component can react to this by fetching more results and appending them to the
list of menu items provided to the Lookup. These new items will then be displayed within the menu.

<cdx-demo-wrapper>
<template v-slot:demo>
<lookup-with-fetch />
</template>
<template v-slot:code>

<<< @/../component-demos/lookup/examples/LookupWithFetch.vue

</template>
</cdx-demo-wrapper>

### Clearable, with start icon

Props of the TextInput component can be bound to Lookup and will be passed down to the TextInput
component inside of it, so you can take advantage of features like the "clear" button and icons.

<cdx-demo-wrapper>
<template v-slot:demo>
<lookup-clearable-start-icon />
</template>
<template v-slot:code>

<<< @/../component-demos/lookup/examples/LookupClearableStartIcon.vue

</template>
</cdx-demo-wrapper>

### With placeholder

Attributes (except for `class`) will fall through to the input element, so you can set things like
`placeholder` on the Lookup component and they'll be applied to the input.

<cdx-demo-wrapper>
<template v-slot:demo>
<lookup-with-placeholder />
</template>
<template v-slot:code>

<<< @/../component-demos/lookup/examples/LookupWithPlaceholder.vue

</template>
</cdx-demo-wrapper>

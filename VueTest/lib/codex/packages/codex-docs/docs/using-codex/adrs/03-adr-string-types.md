# 03) String types in TypeScript

Date: 2021-10-14

## Status

Accepted

## Context
Some components have props that take only certain predefined string values. For example, the Button
component has a `type` prop that can be one of `'normal'`, `'primary'` or `'quiet'`. In general,
components that offer more than two mutually exclusive modes tend to use a string prop to allow
the caller to choose between these modes.

For props like these, we want to define their types in TypeScript, so that we benefit from type
checking. For example, TypeScript could help us catch typos or invalid values in component code
(like `type === 'queit'`) or in component usage (like `<cdx-button type="secondary">`).

## Considered actions

### Enums
We could define these types as enums, which would look like this:
```typescript
// Type definition:
enum ButtonType {
	Normal = 'normal',
	Primary = 'primary',
	Quiet = 'quiet'
};

// Validator:
function isButtonType( s: unknown ): s is ButtonType {
	return typeof s === 'string' && Object.values( ButtonType ).includes( s );

	// At the time of writing, we couldn't use Object.values() or .includes() yet
	// because we were targeting ES6. The ES6 version of the above would be:
	return typeof s === 'string' && Object.keys( ButtonType ).some(
		( key ) => ButtonType[ key as keyof typeof ButtonType ] === val
	);
}
```
However, string values can't be assigned to an enum type directly, you have to get the value
from the enum. In other words, if something expects a `ButtonType` and you pass in `'primary'`,
TypeScript throws an error. You have to pass in `ButtonType.Primary` instead (even though that
compiles to the same value). This means that code like `<cdx-button type="primary">` doesn't pass
TypeScript validation. Instead, code in external components using this prop
would have to look like this:
```vue
<template>
	<div>
		<cdx-button :type="ButtonType.Primary">...</cdx-button>
	</div>
</template>
<script>
import { defineComponent } from 'vue';
import { CdxButton, ButtonType } from 'codex';
export default defineComponent( {
	components: { CdxButton },
	data: () => ( {
		// Pass through ButtonType so that it's available to the template
		ButtonType
	} )
} );
</script>
```

#### Advantages
- Type definition syntax is simple
- Each string value is only defined once, there is no duplication
- A list of all valid values is provided automatically (for use in validators, or code that
  needs to iterate over all possibilities)

#### Disadvantages
- Setting a value for the prop in a template doesn't work with plain strings as one might expect,
  extra code is needed to make it work.

### Union types
Instead of using enums, we could manually define a union type, like this:
```typescript
// Type definition:
type ButtonType = 'normal' | 'primary' | 'quiet';
const ButtonTypes : readonly ButtonType[] = [ 'normal', 'primary', 'quiet' ];

// Validator:
function isButtonType( s: unknown ): s is ButtonType {
	return typeof s === 'string' &&
		( ButtonTypes as readonly string[] ).includes( s );

	// At the time of writing, we couldn't use .includes() yet because we were
	// targeting ES6. The ES6 version of the above would be:
	return typeof s === 'string' &&
		( ButtonTypes as readonly string[] ).indexOf( s ) !== -1;
}
```
Unlike with enums, string values can be used directly, so code like `<cdx-button type="primary">`
works fine.

#### Advantages
- Type definition syntax is simple
- Easier to use for end users because plain string values can be used in templates

#### Disadvantages
- An array of all values isn't provided automatically, it has to be created manually
- Each string value is duplicated, once in the type definition and once in the array

### Union types derived from const arrays
To eliminate the duplication of the string values, we could define the array first, then
derive the type from it, like this:
```typescript
// Type definition:
const ButtonTypes = [ 'normal', 'primary', 'quiet' ] as const;
type ButtonType = typeof ButtonTypes[ number ];

// Validator:
function isButtonType( s: unknown ): s is ButtonType {
	return typeof s === 'string' &&
		( ButtonTypes as readonly string[] ).includes( s );

	// At the time of writing, we couldn't use .includes() yet because we were
	// targeting ES6. The ES6 version of the above would be:
	return typeof s === 'string' &&
		( ButtonTypes as readonly string[] ).indexOf( s ) !== -1;
}
```
This results in `ButtonType` being the same union type as in the "Union types" alternative
(`'normal'|'primary'|'quiet'`). Just as with the "Union types" alternative, using plain string
values in templates (e.g. `<cdx-button type="primary">`) is allowed.

#### Advantages
- Each string value is only defined once, there is no duplication
- A list of all valid values is provided, as an array (easier to use than an object)
- Easier to use for end users because plain string values can be used in templates

#### Disadvantages
- The type definition syntax is more complicated

## Decision
We decided to use union types derived from const arrays.

## Consequences

Our type definitions won't have to duplicate string values, but they'll be more complicated.
End users will have a simpler experience, and validating these types or iterating over all possible
values will be easier too.

For details on how string types are structured, see
[the Working with TypeScript section](../../contributing/contributing-code/typescript#string-types).
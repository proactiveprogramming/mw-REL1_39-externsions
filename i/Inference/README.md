# Inference

This extension for Mediawiki adds a simple inference engine to query and build sets of entities from a Wikibase repository.

It is an alternative way to query the relations between entities, where selectors are used to manipulate an internal set of selected claims, and selectors can be chained to form more complex propositions. In a final step parts the internal structure of the claims or even the entities can be returned. The final step will involve an implicit filter operation, so nil will not be returned if possible. The implicit filtering will not change the internal selection, thus further processing of the selection is possible.

The module is a work in progress, and is not ready for production.

## Concept

The module must be `required` and will then expose a number of methods. An object with a set of claims is then created like the following

```lua
local inference = require 'Module:Inference'
local set = inference.create('Q1') -- Universe
```

The set of claims are the ids of the objects selected entries. They refer an internal cache of all known claims, and an other internal cache all known entities. Because the claims and the entities are expected to remain the same, they are assumed to be non-mutable, they should not change in any way. If they do information will leak between accesses, and later accesses might even fail. (Perhaps block changing of the entities?)

It is possible to create a set consisting of several entities

```lua
local inference = require 'Module:Inference'
local set = inference.create(
  'Q308', -- Mercury
  'Q313', -- Venus
  'Q2', -- Earth
  'Q111' -- Mars
)
```

The individual claims are not jumbled together, they still belong to each individual entity.

This set can be filtered down into a smaller set, given the various field in each claim

```lua
local inference = require 'Module:Inference'
local set = inference.create( 'Q308', 'Q313', 'Q2', 'Q111' ):property( 'P156' )
```

This actually has a size of five, as [P156 for Q111](https://www.wikidata.org/wiki/Q156#P156) has two entries. The size can be found with `:size()`.

Other ways to filter down the set is by using `:type()`, `:rank()`, `:snaktype()`, `:datatype()`, and `:valuetype()`. Each one take string arguments or functions, accumulating claims that somehow matches.

The selection can be turned into tables at different depth in the model

```lua
local inference = require 'Module:Inference'
local set = inference.create( 'Q308', 'Q313', 'Q2', 'Q111' ):property( 'P156' ):getEntities()
```

This will return a list of the four entities, as all four of them has the [P156](https://www.wikidata.org/wiki/Property:P156) property. Other extracts are `:getClaims()`, `:getClaims()`, `:getProperties()`, `:getMainsnaks()`, `:getDatavalues()`, and `:getValues()`. These methods does not compress the set and will give five entries.

There are also two methods to format the returned values so they can be readily used on a page. One is the `:getPlain()` which returns a properly escaped text, and and the other one is the `:getRich()` which returns a formatted wikitext form.

All methods with names on the _get*_-form will return the selection without changig it in any way.

## Query

A user available method _query_ will use an [xpath-like](https://en.wikipedia.org/wiki/XQuery#XPath) or [selector-like](https://en.wikipedia.org/wiki/Cascading_Style_Sheets#Selector) query-style to extract entries from the requested entities at the repository.

One important point to notice; no fetch of new entities will be done unless the query explicitly say so, and it will only be done in a strictly forward fashion as parsing progresses. The parser will not do backtracking. This is to avoid unwanted loops, which will otherwise empty the load budget fast. In user space a fetch is written as a forward slash, and selectors are put inside square brackets.

Given a user call like

```mediawiki
{{#invoke:Inference|query|Q20[P31][rank preferred]/}}
```

then this will be rewritten into a call like

```lua
local inference = require 'Module:Inference'
local result = inference( 'Q20[P31][rank preferred]/' )
  :plain()
```

which is roughly similar to

```lua
local inference = require 'Module:Inference'
local result = inference( 'Q20 [P31] [rank preferred]/' ).create( 'Q20' )
  :property( 'P31' )
  :rank( 'preferred' )
  :fetch()
  :plain()
```

If there are no specific root entity then the current one can be given, or simply implicitly used (could be dropped)

```mediawiki
{{#invoke:Inference|query|.[P31][rank preferred]/}}
{{#invoke:Inference|query|[P31][rank preferred]/}} -- could be dropped
```

### Selectors

Selectors are operators that reduce the set of statements. Any statements kept must satisfy the selector. In particular, a selector will never grow a larger set.

Property is to the left, compared value to the right.

<dl>
  <dt> exist (ex) </dt><dd> Selector is truty if property exist. </dd>
  <dt> equal (eq) </dt><dd> Selector is truty if value for property is equal to given value (within the property values precision). </dd>
  <dt> contains (co) </dt><dd> Selector is truty if value for property contains the given value. </dd>
  <dt> starts (st) </dt><dd> Selector is truty if value for property starts with the given value. </dd>
  <dt> ends (en) </dt><dd> Selector is truty if value for property starts with the given value. </dd>
</dl>

Selectors might include a modifier

## Missing

- [ ] Handling of ''qualifiers'' and ''references'' are not implemented.
- [ ] Parsing of path statements
- [ ] Fetch method

## Examples

```mediawiki
{{#invoke:Inference|query|format=dump}}
```

```mediawiki
{{#invoke:Inference|query|Q20|format=dump}}
```

```mediawiki
{{#invoke:Inference|query|Q20|format=dump|qualifier|reference}}
```

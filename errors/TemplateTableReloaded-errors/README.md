The TemplateTableReloaded extension displays tables based on template data.  This extension improves on the TemplateTable extension, but it is not a drop-in replacement.

## Installation

1. Download the source code.
1. Create a new folder in your MediaWiki extensions directory titled TemplateTableReloaded.
1. Copy the source files into the new folder.
1. Add the following at the bottom of your LocalSettings.php.

        require_once "$IP/extensions/TemplateTableReloaded/TemplateTable.php";

## Configuration Options

Defaults:

    $wgTemplateTableDefaultRowLimit = 500;
    $wgTemplateTableMaxRowLimit = 1000;
    $wgTemplateTableDefaultClasses = 'wikitable';
    $wgTemplateTableTagName = 'ttable';

The row limit options are used to avoid excessive database queries.

The default classes variable is used to specify classes you want in the `class` attribute of the table tags output.  This can be overridden on a per-use basis with the class attribute on the ttable tag.  The class `ttable` will always be appended regardless.

The tag name is configurable in case you prefer a different name or there is a name conflict with other extensions.

## Special Page

The special page is called Special:TemplateTable and it supports most of the options the tag supports.

This page is useful for looking up content or linking to a table without creating a page specifically to hold a table.

## Usage Options

The only required input is the template name between the `<ttable>` and `</ttable>` tags.  All other parameter are optional.  In some cases they should not be mentioned at all if you want the default behavior (eg. headers="" is not the same as not specifying headers at all).

    <ttable
      headers=""
      limit=""
      categories=""
      caption=""
      hidearticle
      headerformatter=""
      cellformatter=""
      ...>
        TemplateName
    </ttable>

### headers

Specify a pipe (|) delimited list of template parameter names to use as the headers.  If this attribute is blank, the only header will be the article name.  If this attribute is left out altogether, the headers will include all template parameter names encountered for the given template.

### limit

This limits the number of rows returned.  There are some administrator controlled limits as well (see above).

### categories

This is an optional, pipe (|) delimited list of categories.  If specified, each page must be in every listed category to qualify for display.  If a category is prefixed with `!`, all pages in that category are excluded.

For example, `categories=Cat1|!Cat2|Cat3` will include pages in both Cat1 and Cat3, but exclude any pages also in Cat2.

If you have a category with a name starting with `!`, you can reference it with the namespace to prevent the exclusion behavior.  For example, `categries=Category:!Cat2` will require `!Cat2` instead of excluding `Cat2`.

### caption

This is the table caption to use (eg. `<caption></caption>`).

### hidearticle

If this attribute is specified, the article name for each template entry will not be included in the table.

### headerformatter

This is useful for modifying the output of each header.  This should point to a template that expects a single, numbered parameter, the name of the header.  The output of the template will be used in place of the header name in the table output.

### cellformatter

This is useful for formatting the output of each data cell.  This should point to a template that expects two, numbered parameters, the name of the column and the value for this cell.  The output will be used in place of the value for this cell in the table output.

### Table attributes

Any additional attributes allowed by MediaWiki on table tags, will be passed through to the table tag output.  If the class attribute is specified, the ttable class will always be appended and any other default classes (see configuration above) will be removed.

## Examples

This is the most simple invocation.  This displays all references to the `Data` template.

    <ttable>Data</ttable>

Displays a JS sortable table of references to the Data template in the Cat3 category.  The headers a limited to the `name` parameter, and output is limited to 10 rows.

    <ttable categories='Cat3' caption="Example" limit=10 headers=name class="wikitable sortable">Data</ttable>

This example hides the article name column from the output.

    <ttable hidearticle>Data</ttable>

This example uses formatters to manipulate the output.  The switch function from the ParserFunctions extension is great for determining which column the template is currently formatting.

    <ttable headerformatter="Data/header" cellformatter="Data/cell">Data</ttable>

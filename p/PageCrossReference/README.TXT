@section DESCRIPTION
PageCrossReference searches an article on a Major Edit save for words that
match page_titles within the article's namespace. If a match is found the
words are changed into an Internal Link and the next page_title is
searched for. There is no distinction between ' ' and '_' separating words.

@section INSTRUCTIONS
Text found in the following conditions are ignored:
The article's own page_title.
page_titles in other page_namespaces.
Subpages
Link tags [[]]
Http tags []
Text between <nowiki> </nowiki>
Text between <pre> </pre>
Text between < and >

@section VARIABLES
Choose page_titles with these or more words.
$wgPageCrossReferenceMinimumWordLength    = 2
Search Headers, true = ignore, false = search.
$wgPageCrossReferenceSkipHeaders          = false
Never choose these titles if found, assume underscores for spaces.
Black List has the form array( 'page_title' ), replace spaces
with underscores.
$wgPageCrossReferenceBlackList            = array()

@section INSTALLATION
PageCrossReference has no dependencies.
Copy and paste the section below in LocalSettings.php.
# BEGIN PageCrossReference
require_once( "$IP/extensions/PageCrossReference/PageCrossReference.php" );
# Place variables below
# This example adds the page "Black Page" to the BlackList.
$wgPageCrossReferenceBlackList = array( 'Black_Page' );
# END PageCrossReference

@section OPERATION
PageCrossReference executes on onArticleSaveComplete then checks for
a true $revision and false $minoredit before proceeding to parseArticle.

On parseArticle $wgPageCrossReferenceLoop is checked because doEdit
invokes onArticleSaveComplete, thus running PageCrossReference twice, so
PageCrossReference runs once per article. The article wiki-text is parsed
into an array. The page_title foreach loop runs once per page_title,
invoking parseContent and passing the text_article_array by reference.

On parseContent each array element is assessed for an existing page_title
internal link or a potential one. If an internal link is found or made
the for loop ends.

PageCrossReference cycles through EVERY page_title in the page table in 
a page_namespace! Ten thousand page_titles means the for loop in 
parseContent runs 10,000 times. This makes PageCrossReference a memory 
and CPU hog. The hog mass will vary per site. A test with 32 page_titles 
on a 1.5 MB article took an extra second to save.

@section KNOWN BUGS
001 Raw page_title followed by internal link. For example, suppose "Main Page" is on line seven and
    "[[Main Page]]" is on line 23. PageCrossReference will convert "Main Page" on line seven to
    "[[Main Page]]" and end the search for "Main Page" while leaving two internal links. This
    violates the OneHit principle but a fix, searching through every article twice, would double the
    work for a rare circumstance. Bug untouched.

002 Subpage. The "/" in a subpage page_title fouls the parser and causes confusion with ratios in
    multi-worded page_titles. The solution is removing subpage searches from PageCrossReference
    and words around "/". Bug fixed.

003 Article page_title intersection across namespaces. The Select query filters out the article
    page_title, therefore articles with the same page_title in other namespaces are ignored. Left
    for the author to edit. Bug untouched.

004 File Upload. File Upload failed in version 0.6. Added Namespace Validator. Bug fixed.

005 page_title page_title. With page_titles in sequence the space between was lost. Bug fixed.
 
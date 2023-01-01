<?php

/**
  * File: MOCA_Text.php
  *
  * Description: Contains strings that are used all over the extension.
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */

# Checks mediawiki exists
if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$egMOCA_txt['help_quickfixpanel_intro'] = "Use the quick fix panel below to improve the page above. The quick fix panel only provides suggestions for improvement which are not compulsory but would be appreciated. Please consider the suggestions before saving the page.";
$egMOCA_txt['help_quickfixpanel_howto_use_header'] = "How do I use the QuickFixPanel?";
$egMOCA_txt['help_quickfixpanel_howto_use'] = "On the left you can see the quick fix panel that allows you to check whether your wiki page is semantically correct. Use the recommendations and possible fixes to make the wiki ontology more complete and this wiki page easier to find by the users of this wiki.";
$egMOCA_txt['help_quickfixpanel_nosuggestions'] = "There are currently no suggestions or information available.";
$egMOCA_txt['help_quickfixpanel_contribution'] = "It's up to you to make your contribution more useful!";
$egMOCA_txt['help_quickfixpanel_moreinformation'] = "More information...";
$egMOCA_txt['help_quickfixpanel_add_categorywizard'] = "Add Category Wizard...";
$egMOCA_txt['help_quickfixpanel_category_header'] = "Categories";
$egMOCA_txt['help_quickfixpanel_category_nocategory'] = "This wiki page has no category assigned.";
$egMOCA_txt['help_quickfixpanel_category_nocategory_r'] = "Consider adding one.";
$egMOCA_txt['help_quickfixpanel_category_onecategory'] = "This wiki page has a category assigned.";
$egMOCA_txt['help_quickfixpanel_category_onecategory_r'] = "No action required.";
$egMOCA_txt['help_quickfixpanel_category_morethanonecategory'] = "This wiki page has more than one categories assigned.";
$egMOCA_txt['help_quickfixpanel_category_morethanonecategory_r'] = "No action required.";
$egMOCA_txt['help_quickfixpanel_category_add_newcategory'] = "Add a new category";
$egMOCA_txt['help_category_definition_head'] = "What is a category?";
$egMOCA_txt['help_category_definition'] = "A category classifies the page as a member of a group of pages that can be classified by a common characteristic. A page can have from none to as many categories as appropriate. By including one or more categories in the wiki page you are making the page easier to find and you are positioning it in a semantic structure. For example let's say we have a wiki page describing a dog. A dog is a type of animal so we can add the category 'Animal' to the dog's wiki page with the following annotation '[[Category:Animal]] '";
$egMOCA_txt['help_category_nocategory'] = "There are no categories in the wiki page you are editing.";
$egMOCA_txt['help_category_oneormorecategory'] = "There are one or more categories in the wiki page you are editing.";
$egMOCA_txt['help_category_recommendation'] = "It is recommended that each wiki page has at least one category.";
$egMOCA_txt['help_category_why_add'] = "You should consider including at least one category in your wiki page. The syntax for a category is of the form <span class='smwi_quickFixPanel_attention'>[[Category:CategoryName]]</span> where you should replace CategoryName by the name of the category. You can instead <span class='smwi_quickFixPanel_attention'>use the wizard below to add a category at the top of your wiki page</span>.";	
$egMOCA_txt['help_type_in_category'] = "Type in the category";
$egMOCA_txt['help_wizard_select_category_suggestions'] = "Consider a category from the suggestions";
$egMOCA_txt['help_wizard_select_category_fromall'] ="Select a category from the list of all categories";
$egMOCA_txt['help_wizard_add_category'] = "Add Category";
$egMOCA_txt['help_no_cat_suggestions_available'] = "No category suggestions currently available.";
$egMOCA_txt['help_no_cat_available'] = "No categories currently available.";
$egMOCA_txt['help_add_category_methods'] = "Use one of the following methods to add a category";


$egMOCA_txt['help_relation_definition_head'] = "What is a relation?";
$egMOCA_txt['help_quickfixpanel_relation_header'] = "Relations";
$egMOCA_txt['help_quickfixpanel_relation_norelation'] = "This wiki page has no links or relations.";
$egMOCA_txt['help_quickfixpanel_relation_norelation_r'] = "Why not add some?";
$egMOCA_txt['help_quickfixpanel_relation_nolinks_without_relationtype'] = "This wiki page has no plain links without a relation type.";
$egMOCA_txt['help_quickfixpanel_relation_nolinks_without_relationtype_r'] = "No action required.";
$egMOCA_txt['help_quickfixpanel_relation_oneormorerelationsandlinks'] = "This wiki page has more than one or more plain links without a relation type.";
$egMOCA_txt['help_quickfixpanel_relation_oneormorerelationsandlinks_r'] = "Review them!";
$egMOCA_txt['help_quickfixpanel_relation_add_newrelation'] = "Add a new relation";
$egMOCA_txt['help_quickfixpanel_relation_view_all_relationandlinks'] = "View all current relations and plain links";
$egMOCA_txt['help_relation_definition'] = "By providing a relation type you are allowing for this wiki's ontology to evolve and improve. Let us give an example of what a relation type is. If for example I am writing a page about an employee (i.e. Steve) and I want to link his/her department (i.e. Sales) page instead of just adding the link, I can add meaning to this link. For example 'part of' the department. The annotation for declaring this would be [[part of::Sales]]. You can use the wizard on the right to add the relation type to the existing link, or you can directly edit the wiki text.";	
$egMOCA_txt['help_relation_why_add'] = "You should consider adding a relation type to your links. The syntax for a relation is of the form <span class='smwi_quickFixPanel_attention'>[[RelationType::DestinationPage]]</span> where you should replace RelationType by the type of the relation and the DestinationPage with the destination. You can instead <span class='smwi_quickFixPanel_attention'>use the wizard below to add a relation at the top of your wiki page if adding a new relation or edit the link you have chosen at its existing position</span>.";
$egMOCA_txt['help_relation_nolinks_without_relationtype'] = "All relations have been given a relation type. No action required.";
$egMOCA_txt['help_relation_norelation'] = "There are no links or relations in this wiki page. Why not add some!?";
$egMOCA_txt['help_relation_norelationtype'] = "This link hasn't got a relation type attached to it.";
$egMOCA_txt['help_relation_norelationtype2'] = "This page has some links that don't have a relation type.";
$egMOCA_txt['help_relation_hasrelationtype'] = "All links in this page have a relation type!";
$egMOCA_txt['help_relation_recommendation'] = "It is recommended that internal links are given a relation type.";
$egMOCA_txt['help_quickfixpanel_add_relationwizard'] = "Add/Edit Relation Wizard...";
$egMOCA_txt['help_relation_no_relations_found'] = "No relations or plain links found in the wikitext";
$egMOCA_txt['help_no_rel_available'] = "No relation suggestions currently available.";
$egMOCA_txt['help_add_relation_methods'] = "Use one of the following methods to add a relation";
$egMOCA_txt['help_type_in_relation'] = "Type in the relation type";
$egMOCA_txt['help_wizard_select_relation_suggestions'] = "Consider a relation type from the suggestions";
$egMOCA_txt['help_wizard_select_dpage'] = "Now, provide the destination page";
$egMOCA_txt['help_typein_dpage'] = "Type in the destination page";
$egMOCA_txt['help_wizard_add_alt'] = "Would you like to include an alternative text?";
$egMOCA_txt['help_typein_alt'] = "Type in the alternative text";
$egMOCA_txt['help_wizard_add_relation'] = "Add Relation";

$egMOCA_txt['help_quickfixpanel_property_header'] = "Properties";
$egMOCA_txt['help_property_definition_head'] = "What is a property?";
$egMOCA_txt['help_quickfixpanel_noproperty'] = "This wiki page has no properties.";
$egMOCA_txt['help_quickfixpanel_noproperty_r'] = "Why not add some?";
$egMOCA_txt['help_quickfixpanel_oneproperty'] = "This wiki page one property assigned to it.";
$egMOCA_txt['help_quickfixpanel_oneproperty_r'] = "Why not add some more?";
$egMOCA_txt['help_quickfixpanel_morethanoneproperty'] = "This wiki page more than one property assigned to it.";
$egMOCA_txt['help_quickfixpanel_morethanoneproperty_r'] = "No action required.";
$egMOCA_txt['help_quickfixpanel_recommendedexists'] = "This page already has some properties but this type of page typically contains some other properties as well.";
$egMOCA_txt['help_quickfixpanel_recommendedexists_r'] = "Check if any of the recommended properties are suitable for this page";
$egMOCA_txt['help_property_noproperty'] = "There are no properties assigned to the wiki page you are editing.";
$egMOCA_txt['help_property_oneormore'] = "There are one or more properties in the wiki page you are editing.";
$egMOCA_txt['help_property_recommendedexists'] = "Although this page already includes one or more properties check the some of the properties recommended below to add to you page.";
$egMOCA_txt['help_property_recommendation'] = "A page to have as many properties as necessary.";
$egMOCA_txt['help_quickfixpanel_property_add_newproperty'] = "Add a new property";
$egMOCA_txt['help_quickfixpanel_add_propertywizard'] = "Add Property Wizard...";
$egMOCA_txt['help_property_definition'] = "A property is a value that describes one of the characteristics of the object being described in the wiki page. For example if we are describing the USA we can say that has the property 'states' and that the value of this property is 52. This will have the following syntax '[[states:=52]]' or '{{states|52}}'. Use the panel below to add a new property.";	
$egMOCA_txt['help_property_why_add'] = "You should consider adding a property to your page. The syntax for a relation is of the form <span class='smwi_quickFixPanel_attention'>[[PropertyName:=PropertyValue]]</span> or <span class='smwi_quickFixPanel_attention'>{{PropertyName|PropertyValue}}</span> where you should replace PropertyName by the name of the property and the PropertyValue with the value of the property. You can instead <span class='smwi_quickFixPanel_attention'>use the wizard below to add a property at the top of your wiki page</span>.";
$egMOCA_txt['help_propertyvalue_definition_head'] = "What is a property value?";
$egMOCA_txt['help_propertyvalue_definition'] = "A property value is the value you give to page property. For example if you have a page about a dog that has the property legs then this property's value is 4.";
$egMOCA_txt['help_no_prop_available'] = "No property suggestions currently available.";
$egMOCA_txt['help_wizard_select_property_suggestions'] = "Consider one of the following methods to add a property";
$egMOCA_txt['help_type_in_relation'] = "Type in the relation type";
$egMOCA_txt['help_select_property_suggestion'] = "Consider a relation type from the suggestions";
$egMOCA_txt['help_wizard_select_pvalue'] = "Now, provide the property value";
$egMOCA_txt['help_type_in_pvalue'] = "Type in the property value";

$egMOCA_txt['help_dpage_definition_head'] = "What is a destination page?";
$egMOCA_txt['help_dpage_definition'] = "A destination denotes the internal link to another wiki page within this wiki.";
$egMOCA_txt['help_alt_definition_head'] = "What is alternative text?";
$egMOCA_txt['help_alt_definition'] = "Alternative text is a text you can provide to be used when displaying the wiki page instead of the actual value. For example if we are referring to a property's alternative text then if we have a property birth date and the actual value is 10/08/1986 we might want to use alternative text to state this in the form of 'August 8th, 1986'. Although the value will still be 10/08/1986 we are displaying it differently. Alternative text can also apply to links and relations.";

$egMOCA_txt['star'] = "*";
$egMOCA_txt['must_complete'] = "Make sure you complete these fields";
?>
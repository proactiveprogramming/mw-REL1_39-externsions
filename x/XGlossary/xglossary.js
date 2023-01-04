// encoding: utf-8
// Using JQuery (1.3.2)

// These are “constants” to reference the elements used/modified/created in this script.
var xg_block_class             = ".xg_js_block";
var xg_block_toggle_class      = ".xg_js_block_toggle";
var xg_block_showall_class     = ".xg_js_block_sall";
var xg_block_hideall_class     = ".xg_js_block_hall";
var xg_block_content_class     = ".xg_js_block_content";
var xg_indexlnk_class          = ".xg_js_indexlnk";

// container_id is the (jQuery) identifier of the main text container, the element
// in which the infobox will be constrained (to avoid it spreading on side menus
// and such…).
//var container_id                 = "body"; // A “generic” solution…
//var container_id                 = ".xg_js_container"; // A “generic” solution…
var container_id                 = "#bodyContent"; // A “Blender skin” solution…
var xg_link_class              = ".xg_js_tooltip_generator";
var xg_link_infobox_class      = ".xg_js_tooltip";

/******************************************************************************
 * Show/Hide parts (groups) in the Glossary page.                             *
 *                                                                            *
 * Note that using hidden group content has one side effect: when we open a   *
 * glossary page with a fragment (i.e. a reference to an entry), if this      *
 * entry is in a hidden part, it wont show up automatically – we have to      *
 * detect it and open/show the right group!                                   *
 ******************************************************************************/
var blocks;

function xgBlockShow(block)
{
	var content = $(block).contents().filter(xg_block_content_class);
	if (!content.length) return;
	$(content).show();
	block.shown = true;
	xgSetupInfoboxes();
}

function xgBlockHide(block)
{
	var content = $(block).contents().filter(xg_block_content_class);
	if (!content.length) return;
	$(content).show();
	block.shown = true;
	xgSetupInfoboxes();
}

function xgBlocksShowAll()
{
	// If no toggleable elements, just return!
	if (!blocks) return;
	$(blocks).each(function() {
		var content = $(this).contents().filter(xg_block_content_class);
		if (!content.length) return true; // = continue…
		$(content).show();
		this.shown = true;
	});
	xgSetupInfoboxes();
	return false; // Don’t want bubbling up…
}

function xgBlocksHideAll()
{
	// If no toggleable elements, just return!
	if (!blocks) return;
	$(blocks).each(function() {
		var content = $(this).contents().filter(xg_block_content_class);
		if (!content.length) return true; // = continue…
		$(content).hide();
		this.shown = false;
	});
	xgSetupInfoboxes();
	return false; // Don’t want bubbling up…
}

function xgBlocksSetup()
{
	blocks = $(xg_block_class);
	// If no toggleable elements, just return!
	if (!blocks) return;
	
	// Else, attach events to each block, and hide everything…
	// Note we bind click event on everything inside the matched elements, so we
	// explicitly unbind them from potential internal links…
	$(blocks).each(function() {
		// Toggle of current block…
		$(this).find(xg_block_toggle_class).bind(
			'click', {"block": this}, function (e) {
				if (e.data.block.shown) {
					$(e.data.block).contents().filter(xg_block_content_class).hide();
					e.data.block.shown = false;
					xgSetupInfoboxes();
					return false; // Don’t want bubbling up…
				}
				else {
					$(e.data.block).contents().filter(xg_block_content_class).show();
					e.data.block.shown = true;
					xgSetupInfoboxes();
					return false; // Don’t want bubbling up…
				}
		})
			.find("a").click(function() {
				$(window)[0].location = $(this).attr("href");
				return false;})
			.end()
		.end()
		// Show all blocks…
		.find(xg_block_showall_class).click(xgBlocksShowAll)
			.find("a").click(function() {
				$(window)[0].location = $(this).attr("href");
				return false;})
			.end()
		.end()
		// Hide all blocks…
		.find(xg_block_hideall_class).click(xgBlocksHideAll)
			.find("a").click(function() {
				$(window)[0].location = $(this).attr("href");
				return false;});
	});
	xgBlocksHideAll();
	xgSetupInfoboxes();
}

$(document).ready(xgBlocksSetup);

// Here we periodically (default: each 100ms) check the page hash/fragment, to
// open right block when it changes…
// If a “changed” event existed on this property, it would be better!
var hash = "";
function xgCheckHash()
{
	// Check if page fragment has changed (to open relevant block if needed…).
	if (hash != location.hash) {
		hash = location.hash;
		var blk = $(blocks).filter(":has(" + hash + ")")[0];
		if (blk) xgBlockShow(blk);
		location.hash = hash;
	}
}
$(document).ready(function () {window.setInterval("xgCheckHash();", 100)});

/******************************************************************************
 * The pop-up infobox stuff…                                                  *
 ******************************************************************************/

// This function positions a given glossary link infobox.
function xgPosInfobox(glnk)
{
	// Get the infobox element itself.
	var ifb = $(glnk).contents().filter(xg_link_infobox_class)[0];
	if (ifb == null) return;
	// Get infobox offset-parent (i.e. element from which it is offset in
	// absolute positionning).
	var offpar = $(ifb).offsetParent()[0];
	if (offpar == null) return;
	// Get the general “text” container (i.e the one in which infobox will be
	// contrained).
	var cont = $(container_id)[0];
	if (cont == null) return;
	
	// Get the position of the link, relative to viewport.
	var pos_top = $(glnk).offset().top;
	var pos_left = $(glnk).offset().left;
	// Get OUTER size of infobox.
	var ifb_height = $(ifb).outerHeight();
	var ifb_width = $(ifb).outerWidth();
	// Position vertically the infobox, so that it does not overlap the
	// container’s top border (however, it might go beyond the bottom one…).
	var ref_y = $(cont).offset().top;
	// If “correct” y-position (relative to container) < 0, put it *under* the
	// generator element…
//	alert(pos_top-(ifb_height + 5)-ref_y);
	if (pos_top-(ifb_height + 5)-ref_y < 0)
	{
		pos_top += $(glnk).height() + 5; // 5px of “margin”.
	}
	else pos_top -= ifb_height + 5; // 5px of “margin”.
	// Position horizontally the infobox, so that it does not overlap the
	// container’s borders, perhaps modifying its width…
	var aw = $(cont).width();
	var ref_x = $(cont).offset().left;
	// If current x-position (relative to container)+width > available width…
	if (pos_left-ref_x+ifb_width > aw)
	{
		// If current width > available width, x-pos = container offset, and reduce infobox width!
		if (ifb_width > aw)
		{
			// Here we need the difference between “outer” and “css” widths of ifb.
			var ifb_dwidth = ifb_width - $(ifb).width();
			pos_left = ref_x;
			$(ifb).css('minWidth', (aw/2) - ifb_dwidth).css('maxWidth', aw - ifb_dwidth);
		}
		// Else, just “bring back” the infobox to the left…
		else pos_left = ref_x + aw - ifb_width;
	}
	// Make position of infobox relative to the infobox offset-parent.
	pos_top -= $(offpar).offset().top;
	pos_left -= $(offpar).offset().left;
	$(ifb).css('top', Math.round(pos_top)).css('left', Math.round(pos_left));
}

// Here we position all the glossary links infoboxes, to float just over their
// own link…
function xgSetupInfoboxes()
{
	// Find all elements wrapping a glossary link.
	var glnks = $("*").filter(xg_link_class).filter(':visible');
	// For elements (glossary links) in glnks, apply the xgPosInfobox() func,
	// and bind the hover/focus/blur events…
	$(glnks).each(function() {
		// Do not process shortdesc-empty links!
		if ($(this).find(xg_link_infobox_class).text() == "") return true;
		$(this).contents().filter("a").unbind();
		xgPosInfobox(this);
		$(this).contents().filter("a").hover(
			function() {var obj = $(this).siblings(xg_link_infobox_class);
			            if($(obj).contents().length) $(obj).css("visibility", "visible");},
			function() {var obj = $(this).siblings(xg_link_infobox_class);
			            $(obj).css("visibility", "hidden");}
		).focus(function() {var obj = $(this).siblings(xg_link_infobox_class);
			                if($(obj).contents().length) $(obj).css("visibility", "visible");})
		 .blur(function() {var obj = $(this).siblings(xg_link_infobox_class);
			               $(obj).css("visibility", "hidden");});
	});
}

$(document).ready(xgSetupInfoboxes);
$(window).resize(xgSetupInfoboxes);


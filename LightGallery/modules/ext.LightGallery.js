( function ( $ ) {
    //remove the link to the file:image page and replace it with a link to the original file to be displayed inside the LightGallery modal window.
    $(".LightGallery .gallerybox").each(function ()
	{
        "use strict";

		var imgLink = $(this).find('a'),
			origImg = $(this).find('img'),
			origImgSrc = origImg.attr("src"),
			imgSrcParts,
			newImgSrc,
			imgCaption = $(this).find(".gallerytext"),
			imgTitle,
			isGallery = $(this).parent('.gallery').length;

		// region link
		//break up the image link into an array
		imgSrcParts = origImgSrc.split("/");


		if ($.inArray("thumb", imgSrcParts) < 0)
		{
			// If true, this is not the thumbnail. Original is probably smaller than Thumb size.
			newImgSrc = origImgSrc;
		}
		else
		{
			//remove "thumb" from path
			imgSrcParts.splice($.inArray("thumb", imgSrcParts), 1);

			//remove thumbnail filename (from the end of the src)
			imgSrcParts.splice(imgSrcParts.length - 1, 1);

			//re-assemble the path
			newImgSrc = imgSrcParts.toString().replace(/\,/g, "/");
		}

		imgLink.attr("href", newImgSrc);

		// endregion

		// region gallery
		//attach alt or caption as title
		if(isGallery)
		{
			imgTitle = $(origImg).attr("alt") || $(imgCaption).find('p').html().trim();
			//set up for light gallery
		}
		else
		{
			imgTitle = $(origImg).attr("alt") || $(this).find(".thumbcaption").text().trim();
		}

		imgTitle = (imgTitle !== "")?imgTitle + " - ":"";

		$(this).attr({"data-src": newImgSrc, "data-sub-html": "." + imgCaption.attr("class")})

		// endregion

    });

    //now set LightGallery on all thumbnail links
    //lgLightGalleryOptions is set in LocalSettings.php
    //example {closeBtn:false}
    $(".LightGallery").lightGallery(lgLightGalleryOptions);
}( jQuery ) );
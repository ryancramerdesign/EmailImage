<?php

/**
 * Render a list of email images
 *
 * This function should continue working if you copy/paste it elsewhere or
 * run from another page or template.
 *
 * You should feel free to repurpose or replace this function with your own.
 *
 * @param int $maxImages Maximum number of images to display per pagination
 * @param int $maxWidth Maximum with of any image (or 0 to ignore)
 * @param int $maxHeight Maximum height of any image (or 0 to ignore)
 * @return string Rendered markup
 *
 */
function renderEmailImages($maxImages, $maxWidth, $maxHeight) {

	$out = '';
	$page = wire('page');
	$maxImages = (int) $maxImages; 
	$maxWidth = (int) $maxWidth; 
	$maxHeight = (int) $maxHeight; 
	$dateFormat = __('Y/m/d g:i a'); 

	if($maxImages < 2) throw new WireException('maxImages must be 2 or more'); 

	if($page->template != 'email-images') {
		// ensures this function still works if used on some other page
		$page = wire('pages')->get('template=email-images');
		if(!$page->id || !$page->viewable()) return __('Nothing to show');
	}

	if(!$page->numChildren) return __('No images yet!');
	$children = $page->children("sort=-created, limit=$maxImages");

	foreach($children as $child) {

		$date = date($dateFormat, $child->created); 

		$out .= "\n<div class='email-image'>" . 
			"<h2>$child->title</h2>" . 
			"<p class='email-image-date'>$date</p>";

		foreach($child->email_images as $image) {
			if($maxWidth && $image->width > $maxWidth) $image = $image->width($maxWidth); 
			if($maxHeight && $image->height > $maxHeight) $image = $image->height($maxHeight); 
			$out .= "<p class='email-image-photo'><img src='$image->url' alt='$image->description' /></p>";
			if($child->email_image_body) $out .= "<p class='email-image-body'>$child->email_image_body</p>";
		}

		$out .= "</div>";
	}

	$pager = $children->renderPager(); // pagination nav, when applicable

	return $pager . $out . $pager;
}



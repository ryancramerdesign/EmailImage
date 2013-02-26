<!DOCTYPE html>
<html lang="en"> 
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- 

	Template file: email-images.php	for EmailImage ProcessWire module. 
	This is just an example template that you will likely want to replace or repurpose. 

	-->

	<title><?php echo $page->title; ?></title>

	<style type='text/css'>
		/* example styles, none are required */ 
		body { font-family: Georgia; color: #444; }
		a { color: red; }
		.email-images { text-align: center; }
		.email-image { padding: 1em; border-top: 1px dotted #999; }
		.email-image img { max-width: 100%; }
		.MarkupPagerNav li { list-style: none; display: inline; padding: 0 0.1em; }
		.MarkupPagerNav a { background: #ddd; color: #333; padding: 3px 10px; text-decoration: none; }
		.MarkupPagerNavOn a, .MarkupPagerNav a:hover { background: #333; color: #fff; }
	</style>
</head>
<body>

	<div class='email-images'>

		<h1><?php echo $page->title; ?></h1>

		<?php 

		// Render email images. 10=images per page, 1200=max width, 700=max height
		echo $modules->get('EmailImage')->render(10, 1200, 700); 

		// Prefer to generate your own markup? Here's a snippet to get you started: 
		// 
		// foreach($page->children as $child) {
		//	$date = date('Y/m/d g:i a', $child->created); 
		//	echo "<div class='email-image'><h2>$child->title</h2><p>$date</p>";
		//	foreach($child->email_images as $img) echo "<p><img src='$img->url' alt=''></p>";
		// 	if($child->email_image_body) echo "<p>$child->email_image_body</p>";
		// 	echo "</div>";
		// }

		?>

	</div>

</body>

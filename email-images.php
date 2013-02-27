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

	<!-- optional google fonts link -->
	<link href='http://fonts.googleapis.com/css?family=Metrophobic' rel='stylesheet' type='text/css'>

	<!-- optional/example styles, none of which are actually required -->
	<style type='text/css'>

		/** general **/ 
		body { font-family: 'Metrophobic', sans-serif; color: #444; }
		a { color: red; }
		h1 { font-weight: normal; font-size: 2.5em; }
		h2 { font-weight: normal; font-size: 1.8em; margin-bottom: 0; }

		/** email-images **/ 
		.email-images { text-align: center; }
		.email-image { padding: 1em; border-top: 1px dotted #999; }
		.email-image img { max-width: 100%; border: none; }
		.email-image-date { color: #777; }
		.email-image-body { font-size: 1.2em; }
		.email-image-photos { margin: 1em 0; padding: 0; }
		.email-image-photos li { list-style: none; padding: 0; margin: 1em 0; }

		/** pagination **/ 
		.MarkupPagerNav { padding: 0; }
		.MarkupPagerNav li { list-style: none; display: inline; margin: 0; padding: 0 0.1em; }
		.MarkupPagerNav a { background: #ddd; color: #333; padding: 3px 10px; text-decoration: none; }
		.MarkupPagerNavOn a, .MarkupPagerNav a:hover { background: #333; color: #fff; }
	</style>
</head>
<body>
	<div class='email-images'>

		<h1><?php echo $page->title; ?></h1>

		<?php 

		// Render email images. 5=image sets per page, 1200=max width, 700=max height
		echo $modules->get('EmailImage')->render(5, 1200, 700); 

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

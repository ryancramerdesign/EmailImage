# Email Image Module for ProcessWire

Send photos from your mobile phone to ProcessWire, from anywhere, on-the-fly. 
ProcessWire maintains a chronological gallery that you can simply email photos to.
Works with any e-mail capable device, whether mobile phone, tablet or desktop. 

- Output inspired by the [work of Philipp Reiner](http://panorama.philippreiner.de).
- Discussion leading to this module can be found [here](http://processwire.com/talk/topic/2324-panorama-heidenheim-using-tumblr-on-ios-to-post-to-pw/).
- Written by Horst Nogajski and Ryan Cramer
- Uses email libraries by Manuel Lemos
- License under GNU GPL v2 
- Requires ProcessWire 2.2.13 or newer
- Here is [example output](http://processwire.com/email-images/) from this module. 

## How to Install

1. Setup a new email address at your provider. This address should be 
   dedicated to the purpose of this module. Make the address private and
   cryptic enough so as not to be obvious or discoverable by others. 
2. Place the files included with this module in /site/modules/EmailImage/ 
3. In your admin, go to Modules > Check for new modules. 
4. Click *Install* for the EmailImage module. 
5. Complete all the settings it asks for, per the email you setup in step 1.
6. Check the box to test your connection, and save. 


## How to Use

1. Email an image to the address you configured. 
2. After waiting a minute or two, view the URL /email-images/ in your site. 
   This is a page that was installed for you to display images. 
3. Repeat and enjoy. :)


## How to Customize

While this module has everything working out-of-the-box, it is meant to be
customized to your needs. For example:

* You may want to move or rename the /email-images/ page somewhere else. 
  The EmailImage module keeps track of this page ID, so it is perfectly
  fine to remove or rename it if you want to. 
* Take a look at the /site/templates/email-images.php template file.
  You may wish to replace it entirely with your own code, or you might build
  upon what's there, or use it as-is. But chances are you'll at least want to
  swap in your own header/footer to fit within your site's design. 
* Take a look at /site/modules/EmailImage/EmailImageRender.php. That file 
  contains an example function used to render an image gallery. This is the 
  one called upon by the template file you just looked at. You might copy and
  paste this function somewhere else into your own site, and modify it to 
  suite your own markup needs. Just remember to name it something different!

Please post links to what you create in the [ProcessWire forums](http://processwire.com/talk/). 

## How to Uninstall

You can uninstall this module in the same way as any other module, by checking
the box to "uninstall" from the module settings screen. But please note the 
following warning:

When you uninstall, your system is returned to the state that it was in before
this module was installed. Meaning, the EmailImage pages and images will be 
deleted. So please backup your images somewhere else if you want to lose them
during uninstall. 

After uninstalling, you can safely remove this dir: /site/modules/EmailImage/.

--------------

Copyright 2013

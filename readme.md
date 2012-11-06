# DisplayAnything 3 #

## About ##
A file and image gallery module for Silverstripe 3.0+, forked from our own <a href="http://github.com/codem/displayanything">DisplayAnything for SS 2.4.5+</a>.

<ul>
<li>Multiple file uploading in supported browsers (not Internet Explorer)</li>
<li>Drag and Drop file uploading in supported browsers - Chrome, Firefox, Safari (and maybe Opera)</li>
<li>Uses a mimetype map, not file extensions to determine an uploaded file type</li>
<li>Uses system settings for upload file size</li>
<li>XHR file uploading</li>
<li>100% Flash Free - no plugin crashes or other futzing with incomprehensible errors!</li>
<li>Uses SS3.0 bundled jQuery</li>
<li>Documented, extendable class</li>
<li>$_REQUEST free zone</li>
<li>Currently uses Valum's File Uploader ( http://github.com/valums/file-uploader ). Porting to jQuery Uploader is on the horizon.</li>
</ul>

This module is only compatible with <a href="http://www.silverstripe.org/stable-download/">the Silverstripe 3.0 release</a>.

<img src="./DisplayAnything3/raw/master/examples/readme.png" width="800" style="display:block;margin: 0 auto;" />
The module in action - A CMS view of a gallery associated with a page.

## State ##

We've tested the module on various sites both internal and external and are happy with the performance. We're considering it a release candidate at the moment.

If you would like to contribute to the development of this module, please fork it, hack away and then open a pull request.

If you would like to use a spiffy multi-file uploader and gallery module in SilverStripe 2.4.x, please look at <a href="http://github.com/codem/displayanything">DisplayAnything</a>. Note that development of the v2 module will only include security updates.

## Changes ##
<ul>
<li>Refactored gallery field</li>
<li>Gallery field no longer extends GridField, it's just a FormField</li>
<li>Pass the gallery relation in when creating field</li>
<li>Works with Strict Standards in PHP 5.4+</li>
<li>Move gallery methods to the gallery class & field related methods to the field class</li>
<li>Removed Image Gallery migration handlers as that module is not used in SS3</li>
</ul>

## Upgrading ##
Previous versions were considered beta with API changes possible. We've changed the calling method for creating the gallery field and have removed or combined certain files.

<ol>
<li>As with all SS module upgrades - sign in as admin *before* upgrading</li>
<li>Replace display_anything with the most recent release</li>
<li>Update the gallery field creation. Look at the examples directory for examples.</li>
<li>Run /dev/build to rebuild the site manifest<li>
<li>Browse to your page or dataobject containing the gallery
</ol>

We recommend updating this on your staging site then moving all changes live in a single update.

## TODO ###
<ul>
<li>We'd like the edit handler to open in a CMS panel, not a dialog. If you'd like to try and implement this, go for it.</li>
<li>Translations</li>
<li>Insert usual Internet Explorer line here. Note: we haven't looked at performance in <= IE8.</li>
<li>In PHP 5.4+, Transliterator::Create() is being called without a required argument by the SS Core, we have turned this off for now</li>
</ul>

## Blame/Praise/Annotate ? ##

If you find a bug we'd like to know about it. If you like the module, spread the word on the internets!

<ol>
<li>Please use the Github issue tracker</li>
<li>Please provide the browser name and version</li>
<li>Provide a description of what is happening so that we can reproduce any issues fast.</li>
<li>Links to any examples of the issues that are occurring are very helpful</li>
</ol>

## Installing ##
<ol>
<li>Download and <a href="http://www.silverstripe.org/stable-download/">install SilverStripe 3.0</a></li>
<li>cd /path/to/your/silverstripe/site</li>
<li>Grab the source:
	<dl>
		<dt>Git</dt>
		<dd><code>git clone git@github.com:codem/DisplayAnything3.git display_anything</code></dd>
		<dt>Bzr (requires bzr-git) - note the / in the path</dt>
		<dd><code>bzr branch git://git@github.com/codem/DisplayAnything3.git display_anything</code></dd>
		<dt>Download</dt>
		<dd><code>wget --output-document=display_anything.zip https://github.com/codem/DisplayAnything3/zipball/master</code></dd>
	</dl>
	<br />In all cases the module source code should be located in a directory called 'display_anything'
</li>
<li>run /dev/build (admin privileges required) and possibly a ?flush=1</li>
<li>implement in the CMS - see the 'examples' directory in the source</li>
<li>log into the CMS and start editing</li>
</ol>

## CMS implementation ##
View the <a href="./DisplayAnything3/tree/master/examples">example directory</a> for some sample page, dataobject and template implementations.

## Templates ##
Innumerable gallery plugins with varying licenses exist for image & file lists and viewing of images in a lightbox (Fancybox is good and open source).

By design, DisplayAnything avoids being a kitchen sink, stays light and does not bundle any of these plugins. It's up to you to implement the gallery the way you want it (this saves you having to undo & override any defaults DisplayAnything may set).

View the <a href="./DisplayAnything3/tree/master/examples/templates">example directory</a> for some sample layouts related to the pages in the examples section.

### Ordered Gallery Items ###
You can implement ordered galleries in your frontend template to match yours or someone else's drag and drop admin work on the Gallery. Simply change "GalleryItems" to "OrderedGalleryItems" in the template.

## Watermarking ##
To implement watermarking, use the following image template/html snippet within your gallery control:

```php
<li class="$EvenOdd $FirstLast"><a href="$URL" rel="page-gallery">$WatermarkCroppedImage(90,90)</a></li>
```

You can use any Silverstripe image resizing method supported (SetHeight, SetWidth, CroppedImage, PaddedImage, SetSize) but prefixed with "Watermark".

The module ships with a watermark image called "_wm.png". To implement your own, add an image called "_wm.png" to a directory named the same as your theme. For example, if your theme is "green", add a file of that name to document_root/green/images/.

Watermarking is only enabled if you use the Watermark prefixed template controls.

## Watermark configuration options ###
Use the following in your site config:
+ WatermarkedImage::$opacity (0-100)
+ WatermarkedImage::$position (tr, tl, br, bl). Example br anchors the watermark image to the bottom right of the source image.
+ WatermarkedImage::$padding_x (pixel padding from image edge in the x-axis)
+ WatermarkedImage::$padding_y (pixel padding from image edge in the y-axis)

## Watermark notes ###
+ Uses GD
+ 8 bit PNGs only
+ The watermark source image is not resized
+ The original image is not watermarked, only the thumbs created with "Watermark_METHODNAME_()" get watermarked
+ WatermarkedImageExtension is an extension to Image

## Licenses ##
DisplayAnything is licensed under the Modified BSD License (refer license.txt)

This library links to Ajax Upload (http://valums.com/ajax-upload/) which is released under the GNU GPL and GNU LGPL 2 or later licenses

+ DisplayAnything is linking to Ajax Upload as described in Section 6 of the LGPL2.1 (http://www.gnu.org/licenses/lgpl-2.1.html)
+ You may modify DisplayAnything under the terms described in license.txt
+ The Copyright holder of DisplayAnything is Codem
+ The Copyright holder of Ajax Upload is Andrew Valums
+ Refer to javascript/file-uploader/license.txt for further information regarding Ajax Upload


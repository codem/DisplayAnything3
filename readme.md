# DisplayAnything 3.0a #

## State ##
Note: this is a highly experimental fork of <a href="http://github.com/codem/displayanything">DisplayAnything</a> based on SilverStripe 3.0a2. Don't expect it to work at all. It may eat your images or worse.
If you would like to contribute to the development of this module, please fork it and hack away. If you would like to use a spiffy multi-file uploader and gallery module in SilverStripe 2.4.x, please look at <a href="http://github.com/codem/displayanything">DisplayAnything</a>.

The <a href="https://github.com/codem/DisplayAnything/blob/master/readme.md">entire readme for DisplayAnything</a> applies to this version.
Additionally, to assist with version identification this module will be versioned the same as the pre-release version of SS3.0 it has been tested against.

## Installing ##
<ol>
<li>Download and <a href="http://www.silverstripe.org/silverstripe-3-0-alpha-2-is-here/">install SilverStripe 3.0a2</a></li>
<li>cd /path/to/your/silverstripe/site</li>
<li>Grab the source:
	<dl>
		<dt>Git</dt>
		<dd><code>git clone git@github.com:codem/DisplayAnything.git display_anything</code></dd>
		<dt>Bzr (requires bzr-git) - note the / in the path</dt>
		<dd><code>bzr branch git://git@github.com/codem/DisplayAnything.git display_anything</code></dd>
		<dt>Download</dt>
		<dd><code>wget --output-document=display_anything.zip https://github.com/codem/DisplayAnything/zipball/master</code></dd>
	</dl>
	<br />In all cases the module source code should be located in a directory called 'display_anything'
</li>
<li>run /dev/build (admin privileges required) and possibly a ?flush=1</li>
<li>implement in the CMS (see 'CMS' below)</li>
<li>log into the CMS and start editing</li>
</ol>

## CMS ##
Here is a sample gallery page:

```php
<?php
class GalleryPage extends Page {

	public static $has_one = array(
		'ImageGallery' => 'DisplayAnythingGallery',
	);
	
	public function PublicImageGallery() {
		$gallery = $this->ImageGallery();
		if($gallery->Visible == 1) {
			return $gallery;
		}
		return FALSE;
	}
	
	
	public function getCmsFields() {
		$fields = parent::getCmsFields();
		
		//GALLERY per page
		$gallery = new DisplayAnythingGalleryField(
			$this,
			'ImageGallery',
			'DisplayAnythingGallery'
		);
		$gallery->SetTargetLocation('galleryfiles');
		$fields->addFieldToTab('Root.Gallery', $gallery);
		
		return $fields;
	}
}


class GalleryPage_Controller extends Page_Controller {}
?>
```

## Support ##
+ No support is available for this version of DisplayAnything. The intended audience is developers who want to hack away and get it working alongside SS3 releases.

## Known issues ##
+ Quite a few Javascript errors, check your console for more
+ AJAX requests commonly result in 500 errors e.g  500 (Warning: "get_class() expects parameter 1 to be object, boolean given" at line 270 of /path/to/sapphire/forms/TableListField.php)
+ Possible SS3 compatibility errors

## Hacking ##
+ Two CSS files have been created - "ss_cms_improvements.css" and "ss3_compat.css". The former is suggested improvements to the SS3 UI (pretty sparse) the latter contains specific SS3 compatiblity styles for the module.

## Licenses ##
DisplayAnything is licensed under the Modified BSD License (refer license.txt)

This library links to Ajax Upload (http://valums.com/ajax-upload/) which is released under the GNU GPL and GNU LGPL 2 or later licenses

+ DisplayAnything is linking to Ajax Upload as described in Section 6 of the LGPL2.1 (http://www.gnu.org/licenses/lgpl-2.1.html)
+ You may modify DisplayAnything under the terms described in license.txt
+ The Copyright holder of DisplayAnything is Codem
+ The Copyright holder of Ajax Upload is Andrew Valums
+ Refer to javascript/file-uploader/license.txt for further information regarding Ajax Upload


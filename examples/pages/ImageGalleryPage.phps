<?php
/**
 * ImageGalleryPage
 * @note this is an example Image Gallery page, it contains one DisplayAnythingGallery relation
 */
class ImageGalleryPage extends Page {

	public static $db = array(
	);

	/**
	 * has_one()
	 * 	This page has a single gallery defined. The relation name is 'ImageGallery' but you can call it something else. You can access the gallery in a template using <% control ImageGallery %> (or loop)
	 */
	public static $has_one = array(
		'ImageGallery' => 'DisplayAnythingGallery',
	);
	
	public function getCmsFields() {
		$fields = parent::getCmsFields();

		/**
		 * DisplayAnythingGalleryField is a GridField
		 */
		$gallery = new DisplayAnythingGalleryField('ImageGallery','My Gallery', $this);

		$fields->addFieldToTab('Root.Gallery', $gallery);
		
		return $fields;
	}
}


class ImageGalleryPage_Controller extends Page_Controller {

}
?>
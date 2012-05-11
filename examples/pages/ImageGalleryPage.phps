<?php
/**
 * ImageGalleryPage
 * @note this is an example Image Gallery page, it contains one DisplayAnythingGallery relation
 */
class ImageGalleryPage extends Page {

	public static $db = array(
	);

	public static $has_one = array(
		'ImageGallery' => 'DisplayAnythingGallery',
	);
	
	public function getCmsFields() {
		$fields = parent::getCmsFields();

		$gallery = new DisplayAnythingGalleryField(
			'ImageGallery',
			'DisplayAnythingGallery',
			$this //related dataobject
		);

		$gallery->SetTargetLocation('galleryfiles');
		$fields->addFieldToTab('Root.Gallery', $gallery);
		
		return $fields;
	}
}


class ImageGalleryPage_Controller extends Page_Controller {

}
?>
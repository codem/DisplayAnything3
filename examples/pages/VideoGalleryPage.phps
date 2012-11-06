<?php
/**
 * VideoGalleryPage
 * @note this is an example Video Gallery page, it contains one DisplayAnythingYouTubeGallery relation. Its main goal is to illustrate how you can extend the base class to add extra functionality
 */
class VideoGalleryPage extends Page {

	public static $has_one = array(
		'VideoGallery' => 'DisplayAnythingYouTubeGallery',
	);
	
	public function getCmsFields() {
		$fields = parent::getCmsFields();

		//YOUTUBE VIDEO gallery - a simple extension to the default gallery
		$gallery = new DisplayAnythingGalleryField('VideoGallery','A video gallery',$this->VideoGallery());
		$fields->addFieldToTab('Root.Content.Videos', $gallery);

		return $fields;
	}
}


class VideoGalleryPage_Controller extends Page_Controller {

}
?>
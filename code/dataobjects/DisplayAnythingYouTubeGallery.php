<?php
/**
 * DisplayAnythingYouTubeGallery()
 * @note a gallery of DisplayAnythingYouTubeVideoFile(s), a simple example of how DA can be extended
 */
class DisplayAnythingYouTubeGallery extends DisplayAnythingGallery {
	static $has_many = array(
		'GalleryItems' => 'DisplayAnythingYouTubeVideoFile'
	);
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->ClassName = __CLASS__;
	}
}
?>
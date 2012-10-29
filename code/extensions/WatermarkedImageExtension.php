<?php
/**
 * WatermarkedImageExtension
 * @notee decorates Image, provides some resize methods
 */
class WatermarkedImageExtension extends DataExtension {

	private function getWatermarkedImage() {
		if($this->owner instanceof Image) {
			//return a watermarkable Image object, extending Image
			return new WatermarkedImage($this->owner->getAllFields());
		}
		return FALSE;
	}
	
	private function applyResize($method, $width = 0, $height = 0) {
		if($image = $this->getWatermarkedImage()) {
			switch($method) {
				case 'SetWidth':
				case 'SetHeight':
				case 'PaddedImage':
				case 'SetSize':
				case 'CroppedImage':
					return $image->getFormattedImage($method, $width, $height);
					break;
				default:
					return FALSE;
					break;
			}
		}
		return FALSE;
	}

	public function WatermarkSetWidth($width) {
		return $this->applyResize('SetWidth',$width);
	}

	public function WatermarkSetHeight($height) {
		return $this->applyResize('SetHeight',$height);
	}

	public function WatermarkPaddedImage($width, $height) {
		return $this->applyResize('PaddedImage',$width, $height);
	}

	public function WatermarkCroppedImage($width, $height) {
		return $this->applyResize('CroppedImage',$width, $height);
	}

	public function WatermarkSetSize($width, $height) {
		return $this->applyResize('SetSize',$width, $height);
	}
	
	public function WatermarkLink() {
		$wm = $this->getWatermarkedImage();
		$copy = $wm->SetSize($wm->getWidth(), $wm->getHeight());
		if($copy) {
			unset($wm);
			return $copy->Link();
		}
		return $this->owner->Link();
	}
}
?>
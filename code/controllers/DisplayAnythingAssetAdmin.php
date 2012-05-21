<?php
/**
 * DisplayAnythingAssetAdmin()
 * @note controller for single file related actions
 */
class DisplayAnythingAssetAdmin extends Controller {

	private $gallery_item, $gallery;
	
	static $url_segment = 'galleryfile';//gooms this url segment  ?
	
	static $menu_title = 'Gallery File';
	
	private function handlerField(SS_HTTPRequest $request) {
		if(!$this->gallery) {
			throw new Exception("The associated gallery cannot be found");
		}
		
		$field = new DisplayAnythingGalleryField(
			'DisplayAnythingAdminBackend',//name of the field
			'',//title of the field
			$this->gallery//related dataobject (a page, a dataobject)
		);
		
		$allowed_mime_types = array_flip(explode(",", $this->gallery->Usage()->MimeTypes));
		
		$field->SetMimeTypes($allowed_mime_types);
		
		return $field;
		
	}

	//single gallery item actions
	public function EditFile(SS_HTTPRequest $request) {
		try {
		
			$this->gallery_item = DataObject::get_one('DisplayAnythingFile', "\"DisplayAnythingFile\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($this->gallery_item->GalleryID) . "'");
			
			$field = $this->handlerField($request);
			
			return $field->EditForm($this->gallery_item, $this);
			
		} catch (Exception $e) {
			//print "Failed : {$e->getMessage()}\n";
		}
		return "<p>The file requested does not exist</p>";
	}
	
	public function DeleteFile(SS_HTTPRequest $request) {
		try {
		
			$this->gallery_item = DataObject::get_one('DisplayAnythingFile', "\"DisplayAnythingFile\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($this->gallery_item->GalleryID) . "'");
			
			$field = $this->handlerField($request);
			
			return $field->EditForm($this->gallery_item, $this);
			
		} catch (Exception $e) {
			//print "Failed : {$e->getMessage()}\n";
		}
		return "<p>The file requested does not exist</p>";
	}
	
	//gallery actions
	public function ReloadList(SS_HTTPRequest $request) {
		try {
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			$field = $this->handlerField($request);
			return  $field->ReloadList($this->gallery);
		} catch (Exception $e) {
		}
	}
	
	public function SortItem(SS_HTTPRequest $request) {
		try {
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			$field = $this->handlerField($request);
			return  $field->SortItem($this->gallery);
		} catch (Exception $e) {
		}
	}
	
	//upload actions
	public function Upload(SS_HTTPRequest $request) {
		try {
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			$field = $this->handlerField($request);
			return  $field->Upload($this->gallery);
		} catch (Exception $e) {
		}
	}

}
?>
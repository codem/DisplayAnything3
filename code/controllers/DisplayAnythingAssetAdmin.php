<?php
/**
 * DisplayAnythingAssetAdmin()
 * @note controller for single file related actions
 */
class DisplayAnythingAssetAdmin extends Controller {

	private $gallery_item, $gallery;
	
	static $url_segment = 'galleryfile';//gooms this url segment  ?
	
	static $menu_title = 'Gallery File';
	
	public static $url_handlers = array(
		'$Action/$ID/field/$OtherID' => 'FieldAction',//specific field actions on the EditForm
	);
	
	/** 
	 * handle specific field actions and reroute to EditForm::Field based on OtherID passed in (routed by self::url_handlers)
	 */
	public function FieldAction(SS_HTTPRequest $request) {
		$this->handlePopulate($request);
		$field = $this->handlerField($request);
		$form = $field->EditForm($this->gallery_item, $this);
		return $form->Fields()->dataFieldByName($request->param('OtherID'));
	}
	
	private function handlerField(SS_HTTPRequest $request) {
		if(!$this->gallery) {
			throw new Exception("The associated gallery cannot be found");
		}
		
		$field = new DisplayAnythingGalleryField(
			'DisplayAnythingAdminBackend',//name of the field
			'',//title of the field
			$this->gallery//related dataobject (a page, a dataobject)
		);
		$field->SetMimeTypes();
		return $field;
		
	}
	
	private function handlePopulate(SS_HTTPRequest $request) {
		$this->gallery_item = DataObject::get_one('DisplayAnythingFile', "\"DisplayAnythingFile\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
		if(empty($this->gallery_item->ID)) {
			throw new Exception("Item not found");
		}
		$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($this->gallery_item->GalleryID) . "'");
	}

	//single gallery item actions
	public function EditFile(SS_HTTPRequest $request) {
		try {
		
			$this->handlePopulate($request);
			
			$field = $this->handlerField($request);
			
			$form = $field->EditForm($this->gallery_item, $this);
			
			if($request->isPOST()) {
				try {
					$record = $request->postVars();
					$this->gallery_item->castedUpdate($record);
					$this->gallery_item->write();
					$form->sessionMessage('Saved ;)','good');
				} catch (Exception $e) {
					$form->sessionMessage('Failed to save','bad');
				}
				return $this->redirect($request->getURL());
			} else {
				$result = $this->customise(array(
					'Form' => $form
				))->renderWith('UploadAnythingFileEdit');
				return $result;
			}
		} catch (Exception $e) {
			//print "Failed : {$e->getMessage()}\n";
		}
		return "<p>The file requested does not exist</p>";
	}
	
	public function DeleteFile(SS_HTTPRequest $request) {
		try {
			$this->handlePopulate($request);
			
			$result = $this->gallery_item->delete();
			
			return $result ? 0 : 1;
			
		} catch (Exception $e) {
			//print "Failed : {$e->getMessage()}\n";
		}
		return 0;
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
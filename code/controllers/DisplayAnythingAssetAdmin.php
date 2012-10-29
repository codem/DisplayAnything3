<?php
/**
 * DisplayAnythingAssetAdmin()
 */
class DisplayAnythingAssetAdmin extends Controller {

	private $gallery_item, $gallery;
	
	public static $url_handlers = array(
		'$Action/$ID/field/$OtherID' => 'FieldAction',//specific field actions on the EditForm
	);
	
	public function __construct() {
		parent::__construct();
	}
	
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
		return new DisplayAnythingGalleryField('DisplayAnythingAdminBackend', '', $this->gallery);
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

	/**
	 * DeleteFile
	 * @param $request
	 * @returns int
	 */
	public function DeleteFile(SS_HTTPRequest $request) {
		try {
			$this->handlePopulate($request);
			return $this->gallery_item->delete() ? 1 : 0;
		} catch (Exception $e) {
			//print "Failed : {$e->getMessage()}\n";
		}
		return 0;
	}
	
	/**
	 * AdminLink()
	 * @param $id a  unique identifier related to the action
	 * @param $action string suffix for action link
	 * @todo permissions around obtaining this link (e.g frontend)
	 * @todo prefix for install dir ?
	 */
	public static function AdminLink($action, $id) {
		return Controller::join_links('admin/da', $action, $id);
	}
	
	/**
	 * ReloadList
	 * @param $request
	 * @returns string
	 */
	public function ReloadList(SS_HTTPRequest $request) {
		try {
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			print $this->gallery->GetFileList();
		} catch (Exception $e) {
			print "The item list is not available";
		}
		exit;
	}

	/**
	 * SortItem()
	 * @param $request
	 * @returns string
	 */
	public function SortItem(SS_HTTPRequest $request) {
		try {
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			print $this->gallery->SortItem();
		} catch (Exception $e) {
			print 0;
		}
		exit;
	}
	
	/**
	 * Upload()
	 * @note upload a file into the gallery
	 * @param $request
	 * @todo should this be moved out of the Field and into this controller
	 */
	public function Upload(SS_HTTPRequest $request) {
		try {
			$this->gallery = DataObject::get_one('DisplayAnythingGallery', "\"DisplayAnythingGallery\".\"ID\"='" . Convert::raw2sql($request->param('ID')) . "'");
			print $this->gallery->Upload();
		} catch (Exception $e) {
		}
		exit;
	}

}
?>
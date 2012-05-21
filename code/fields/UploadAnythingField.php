<?php
/**
* UploadAnything, DisplayAnything and friends
* @package display_anything
* @copyright Codem 2011 onwards
* @author <a href="http://www.codem.com.au">Codem / James Ellis</a>
* @note <p>UploadAnything is a base abstract set of classes used for building file uploaders, galleries and file managers in Silverstripe
			<br />DisplayAnything extends and overrides functionality provided by UploadAnything to do just that.
			<br />You can use UploadAnything natively as a simple file uploader and management tool or build your own file management tool off it.</p>
* @note relies on and uses Valum's File Uploader ( http://github.com/valums/file-uploader )
* @note 
		<h4>Background</h4>
		<p>Handle file uploads via XHR or standard uploads.</p>
		<h4>Features</h4>
		<ul>
			<li>Security: uses a mimetype map, not file extensions to determine an uploaded file type</li>
			<li>Integration: uses system settings for upload file size</li>
			<li>Usability: Multiple file uploading in supported browsers (not Internet Explorer)</li>
			<li>Drag and Drop in supported browsers (Chrome, Firefox, Safari)
			<li>XHR file uploading</li>
			<li>100% Flash Free - no plugin crashes or other futzing with incomprehensible errors!</li>
			<li>Has Zero Dependencies on DataObjectManager or Uploadify</li>
			<li>Designed to work with ComplexTableField</li>
			<li>Not reliant on jQuery or ProtoType</li>
			<li>Documented, extendable class</li>
			<li>$_REQUEST not used</li>
		</ul>
* @note that file extensions are not used, except for basic client side validation.
*/

/**
 * UploadAnythingField()
 */
abstract class UploadAnythingField extends GridField {
	private $file = FALSE;
	private $fileKey = 'qqfile';
	private $returnValue = array();
	private $allowed_file_types = array();//an associative array, key is the mime type, value is the extension without a .
	private $tmp_location = array();
	private $upload_file_name = "";
	private $overwrite_file = FALSE;
	private $replace_current = FALSE;
	private $target_location = "";
	private $configuration = array();
	public $show_help = TRUE;//FALSE to not show Upload Help text
	
	//default file and directory permissions
	//if your web server runs as a specific user, these can be altered to make the rw for only that user
	public $file_permission = 0644;
	public $directory_permission = 0755;
	
	protected $relatedDataObject;//dataobject related to this field
	
	/*
	public static $allowed_actions = array(
		'Upload',
		'ReloadList',
		'SortItem',
		'DeleteFile',
		'EditFile',
	);
	
	public static $url_handlers = array(
		'DeleteFile/item/$ID' => 'DeleteFile',
	);
	*/
	
	public function __construct(
			$name,
			$title,
			$relatedDataObject,
			SS_List $dataList = null,
			GridFieldConfig $config = null
		) {
		
		$this->relatedDataObject = $relatedDataObject;
		parent::__construct($name, $title, $dataList, $config);
		$this->SetMimeTypes();
		self::LoadCSS();
		
	}
	
	/**
	 * @param $id a  unique identifier related to the action
	 * @param $id a  unique identifier related to the action
	 * @todo permissions around obtaining this link (e.g frontend)
	 */
	public function AdminLink($action, $id) {
		return Controller::join_links('/admin/da', $action, $id);
	}
	
	/**
	 * GetGalleryImplementation()
	 * @note use this function to get the current gallery implementation
	 * @returns DisplayAnythingGallery
	 * @throws Exception
	 */
	final protected function GetGalleryImplementation() {
		if($this->relatedDataObject instanceof DisplayAnythingGallery) {
			//if the related dataobject is a gallery
			return $this->relatedDataObject;
		} else if($this->relatedDataObject->has_one($this->name)) {
			//if the relatedDataObject is another dataobject having one gallery of this field name
			return $this->relatedDataObject->{$this->name}();
		} else {
			throw new Exception("The DisplayAnythingGalleryField does not have  valid gallery implementation");
		}
	}
	
	
	/**
	 * SetFileKey()
	 * @note allow override of filekey used in upload, allows file replacement to hook into Upload()
	 */
	public function SetFileKey($fileKey) {
		$this->fileKey = $fileKey;
		return $this;
	}
	
	/**
	 * SetPermissions()
	 */
	public function SetPermissions($file = 0644, $directory = 0755) {
		$this->file_permission = $file;
		$this->directory_permission = $directory;
		return $this;
	}
	
	public function GetFilePermission() {
		return $this->file_permission;
	}

	public function GetDirectoryPermission() {
		return $this->directory_permission;
	}
	
	public static function ToBytes($str){
		$val = trim($str);
		$last = strtolower($str[strlen($str)-1]);
		switch($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}
	
	private function WebImageMimeTypes() {
		return array(
			'image/jpg' => 'jpg',
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/x-png' => 'png',//IE sometimes uploads older pre standardized PNG files as this mimetype. Another feather in its c(r)ap
			'image/gif' => 'gif',
			'image/pjpeg' => 'jpg',
		);
	}
	
	/**
	 * SetMimeTypes()
	 * @note by default we just allow images
	 * @param $mime_types pass in mimetype=>extension pairs to override or a string/of,mime/types
	 * @note are you unsure about mimetypes ? http://www.google.com.au/search?q=mimetype%20list
	 * <blockquote>So, why doesn't this use file extensions? File Extensions are part of the file name and have no bearing on the contents of the file whatsoever. 'Detecting' file content by matching the characters after the last "." may provide a nice string to work with but in fact lulls the developer into a false sense of security.
	 		To test this, create a new PHP file, add '<?php print "I am PHP";?>' and save that file as 'image.gif' then upload it using an uploader that allows file Gif files. What happens when you browse to that file ?
	 		UploadAnything requires your developer (or you) to provide it a map of allowed mimetypes. Keys are mimetypes, values are a short blurb about the file. By default it uses the standard mimetypes for JPG, GIF and PNG files.
	 		If you are uploading a valid file and the UploadAnything MimeType checker is not allowing it, first determine it's mimetype and check against the whitelist you have provided. Some older software will save a file in older, non-standard formats.
	 		UploadAnything uses the 'type' value provided by the PHP file upload handler for standard uploads that populate $_FILES. For XHR uploads, UploadAnything uses finfo_open() if available, followed by an image mimetype check, followed by mime_content_type (deprecated). If no mimeType can be detected. UploadAnything refuses the upload, which is better than allowing unknown files on your server.
	 		
	 		If you are using the DisplayAnything file gallery manager, the Usage tab provides a method of managing allowed file types on a per gallery basis.
	 		</blockquote>
	 */
	final public function SetMimeTypes($mime_types = NULL) {
		
		//set from possible gallery usage
		$usage = FALSE;
		$this->allowed_file_types = array();
		if(($gallery = $this->GetGalleryImplementation()) && ($usage = $gallery->Usage())) {
			$mime_types = $usage->MimeTypes;
		}
		
		if(is_string($mime_types)) {
			$chars = DisplayAnythingGalleryUsage::splitterChars();
			$mime_types = array_flip(preg_split("/[" . preg_quote($chars) . "]+/", trim($mime_types, $chars)));
			if(!function_exists('loadMimeTypes')) {
				require(FRAMEWORK_PATH . "/email/Mailer.php");
			}
			if($map = loadMimeTypes()) {
				$web_image_mime_types = $this->WebImageMimeTypes();
				foreach($mime_types as $mime_type=>$value) {
					//framework/email/Mailer.php
					$ext = array_search($mime_type, $map);
					if($ext !== FALSE) {
						$this->allowed_file_types[$mime_type] = $ext;
					} else if(array_key_exists($mime_type, $web_image_mime_types)) {
						//try from our web types
						$this->allowed_file_types[$mime_type] = $web_image_mime_types[$mime_type];
						
					}
				}
			}
		}
		
		//back to basics
		if(empty($this->allowed_file_types)) {
			//nothing set, assume image upload for starters
			$this->allowed_file_types = $this->WebImageMimeTypes();
		}
		
		return $this;
	}
	
	/**
	 * ConfigureUploader()
	 * @param $configuration an array of configuration values (see http://valums.com/ajax-upload/)
	 * @note that action is handled internally and will be overwritten
	 * @note currently supported example configuration:
	 * 			<pre>array(
	 *					'action' => '/relative/path/to/upload',
	 *					'params' => array(), //note that params are passed as GET variables (not POST)
	 *					'allowedExtensions' => array(),//used for basic client side validation only
	 *					'sizeLimit' => 0, //in bytes ?
	 *					'minSizeLimit' => 0, //in bytes ?
	 *					'debug' => true/false,
	 *					...
	 *				)</pre>
	 */
	public function ConfigureUploader($configuration = array()) {
		if(!is_array($configuration)) {
			throw new Exception('Incorrect configuration for UploadAnythingField');
		}
		$this->configuration = $configuration;
		return $this;
	}
	
	/**
	 * OverwriteFile()
	 * @note sets the file field to overwrite if a same-named file is found, this is a configuration item that returns this field to enable chainable setups
	 * @returns UploadAnythingField
	 */
	public function OverwriteFile($replace = FALSE) {
		$this->overwrite_file = $replace;
		return $this;
	}
	
	/**
	 * ReplaceCurrent()
	 * @note triggers the field to replace the relevant file during the upload, triggers from the form returned by EditFile
	 * @param $file the file to replace
	 */
	public function ReplaceCurrent($file) {
		$this->replace_current = $file;
		return $this;
	}
	
	/**
	 * SetTargetLocation()
	 * @param $location a subdirectory of the SS ASSETS_PATH
	 * @note doesn't have to exist just yet as the uploader will create it
	 */
	final public function SetTargetLocation($location) {
		$this->target_location = $location;
		return $this;
	}
	
	/**
	 * LoadUploadHandler()
	 * @note loads the correct handler depending on incoming data - if $_FILES is present, use the standard file save handler, if the XHR request is present, use the XHR backend
	 * @returns object
	 */
	protected function LoadUploadHandler() {
		if (isset($_GET[$this->fileKey])) {
			$this->file = new UploadAnything_Upload_XHR($this->fileKey, $this);
			$this->file->saveToTmp();//saves raw stream to tmp location
		} elseif (isset($_FILES[$this->fileKey])) {
			$this->file = new UploadAnything_Upload_Form($this->fileKey, $this);
		} else {
			throw new Exception("No upload handler was defined for this request");
		}
	}
	
	/**
	 * @note this is a compatibility function with File::setName() - as we aren't dealing with a File object at this point, we can't (and don't want to) use File::setName() as this needs a File instance.
	 * @note in SS3.0+, FileNameFilter abstracts this cleaning away, unlike the SS2.4 which handles cleaning within File::setName
	 * @param $name a raw file name without a file extension
	 * @returns string
	 */
	protected function CleanFileName($name) {
		$filter = Object::create('FileNameFilter');
		return $filter->filter($name);
	}
	
	/**
	 * SetUploadFileName()
	 * @param $uploadPath wherever uploades are saving at
	 * @param $overwrite if TRUE will overwrite the current same-named file in that directory
	 * @note we ensure the filename here matches what SS' File object will morph it into
	 * @todo place a limit on our while loop here? Repeat after me: I don't like recursiveness.
	 */
	protected function SetUploadFileName($uploadPath, $overwrite = FALSE) {
		$pathinfo = pathinfo($this->file->getName());
		$filename = $pathinfo['filename'];
		$ext = $pathinfo['extension'];
		if(!$overwrite && file_exists($uploadPath . "/" . $filename . "." . $ext)) {
			$suffix = 0;
			while (file_exists($uploadPath . "/" . $filename . "." . $ext)) {
				//while the file exists, prepend a suffix to the file name
				$filename = $suffix . "-" . $filename;
				$suffix++;
			}
		}
		$cleaned = $this->CleanFileName($filename);
		if($cleaned == "") {
			throw new Exception("File error: the filename '{$filename}' is not supported");
		}
		$this->upload_file_name = $cleaned . "." . $ext;
		return $uploadPath . "/" . $this->upload_file_name;
	}
	
	public function GetFileName() {
		return $this->upload_file_name;
	}
	
	protected function GetMaxSize() {
		//returns whatever is the minimum allowed upload size - out of FILE or POST
		return min( array( self::GetUploadMaxSize(), self::GetPostMaxSize() ) );
	}
	
	public static function GetUploadMaxSize() {
		return self::ToBytes(ini_get('upload_max_filesize'));
	}
	
	public static function GetPostMaxSize() {
		return self::ToBytes(ini_get('post_max_size'));
	}
	
	final private function CheckAllowedSize() {
		$size = $this->file->getSize();
		
		if ($size == 0) {
			throw new Exception('File is empty');
		}
		
		$postSize = 
		$uploadSize = self::GetUploadMaxSize();
		$msize = round($size / 1024 / 1024, 2) . 'Mb';
		$postSizeMb = round($postSize / 1024 / 1024, 2) . 'Mb';
		$uploadSizeMb = round($uploadSize / 1024 / 1024, 2) . 'Mb';
		
		if ($size > $postSize) {
			throw new Exception("The server does not allow files of this size ({$msize}) to be uploaded. Hint: post_max_size is set to {$postSizeMb}");
		}
		
		if ($size > $uploadSize) {
			throw new Exception("The server does not allow files of this size ({$msize}) to be uploaded. Hint: upload_max_filesize is set to {$uploadSizeMb}");
		}
		
		return TRUE;
	}
	
	/**
	 * CheckAllowedType()
	 * @note grabs file and checks that the mimetype of the file is in our allowed mimetypes
	 */
	final private function CheckAllowedType() {
	
		if(empty($this->allowed_file_types)) {
			throw new Exception("No allowed file types have been defined - for security reasons this file cannot be uploaded.");
		}
		
		$allowed_list = "";
		foreach($this->allowed_file_types as $type=>$ext) {
			$allowed_list .= $type . ($ext ? " (" .strtolower($ext) . ")" : "") . ", ";
		}
		
		$mimeType = strtolower($this->file->getMimeType());
		if(!array_key_exists($mimeType, $this->allowed_file_types)) {
			throw new Exception("This file uploader does not allow files of type '{$mimeType}' to be uploaded. Allowed types: " .  trim($allowed_list, ", ") . ".");
		}
		return TRUE;
	}
	
	/**
	 * GetAllowedExtensions() used solely for client side validation on the filename
	 * @returns array
	 * @throws Exception
	 */
	final private function GetAllowedExtensions() {
		if(empty($this->allowed_file_types)) {
			throw new Exception("No allowed file types have been defined for this uploader.");
		}
		return array_unique(array_values($this->allowed_file_types));
	}
	
	protected function GetAllowedFilesNote() {
		return implode(",", $this->GetAllowedExtensions());
	}
	
	/**
	 * UploadResult()
	 * @note either returns whether the upload has succeeded or prints a JSON encoded string for the upload client
	 * @returns mixed
	 */
	public function UploadResult($return = FALSE) {
		if($return) {
			return $this->Success();
		} else {
			print htmlspecialchars(json_encode($this->returnValue), ENT_NOQUOTES);
			exit;
		}
	}
	
	/**
	 * GetReturnValue() gets current return value for XHR upload
	 * @returns mixed
	 */
	public function GetReturnValue() {
		return $this->returnValue;
	}


	/**
	 * Success() returns TRUE if returnValue is successful
	 * @returns boolean
	 */
	public function Success() {
		return isset($this->returnValue['success']) && $this->returnValue['success'];
	}
	
	/**
	 * CanUpload()
	 * @note can the current member upload ?
	 * @todo more upload permissions, configurable
	 * @returns Member
	 */
	protected function CanUpload() {
	
		$can = ini_get('file_uploads');
		if(!$can) {
			throw new Exception('File uploads are not enabled on this system. To enable them, get your administrator to set file_uploads=1 in php.ini');
		}
	
		$member = Member::currentUser();
		if(empty($member->ID)) {
			throw new Exception("You must be signed in to the administration area to upload files");
		}
		return $member;
	}
	
	/**
	 * UnlinkFile() unlinks a target file
	 * @returns boolean
	 * @todo security around unlinking any path ... :?
	 */
	private function UnlinkFile($target) {
		if(is_writable($target)) {
			unlink($target);
		}
	}
	
	/**
	 * LoadScript()
	 * @note override this method to use your own CSS. Changing this may break file upload layout.
	 */
	public static function LoadScript() {
		
		//have to use bundled jQ or CMS falls over in a screaming heap
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-livequery/jquery.livequery.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-ui/jquery-ui-1.8rc3.custom.js");
		
		Requirements::block(THIRDPARTY_DIR . "/firebug-lite/firebugx.js");//block this out, the little bastard
		Requirements::javascript("display_anything/javascript/file-uploader/client/fileuploader.js");
		Requirements::javascript("display_anything/javascript/display.js");

	}
	
	/**
	 * LoadCSS()
	 * @note override this method to use your own CSS. Changing this may break file upload layout.
	 */
	public static function LoadCSS() {
		Requirements::css("display_anything/css/display.css");
		Requirements::css("display_anything/javascript/file-uploader/client/fileuploader.css");
	}
	
	/**
	 * LoadAssets()
	 * @note override this method to use your own jquery lib file. You should provide a recent version or uploads may fail.
	 */
	protected function LoadAssets() {
		self::LoadScript();
		self::LoadCSS();
	}
	
	/**
	 * GetUploaderConfiguration()
	 * @note you can override this for custom upload configuration. This configuration is for the client uploader and it's worth noting that all options here can be easily changed by anyone with enough browser console knowledge. Validation happens on the server
	 * @todo file extensions allowed
	 * @todo min size, max size per uploader spec
	 * @see "### Options of both classes ###" in the FileUploader client readme.md
	 * @todo check if 'record' is still required...shouldn't be relied on
	 */
	public function GetUploaderConfiguration() {
		try {
		
			$gallery = $this->GetGalleryImplementation();
		
			//work out the upload location.
			$this->configuration['action'] = $this->AdminLink('Upload', $gallery->ID);
			$this->configuration['reload'] = $this->AdminLink('ReloadList', $gallery->ID);
			
			if(!isset($this->configuration['params'])) {
				$this->configuration['params'] = array();
			}
			
			$this->configuration['allowedExtensions']  = $this->GetAllowedExtensions();
	
			//these options are not supported in all browsers
			$this->configuration['sizeLimit'] = $this->GetMaxSize();
			$this->configuration['minSizeLimit'] = 0;
			
			if(!isset($this->configuration['maxConnections'])) {
				$this->configuration['maxConnections'] = 3;
			}
			
			$string = htmlentities(json_encode($this->configuration), ENT_QUOTES, "UTF-8");
			//print $string;
			return $string;
		} catch (Exception $e) {}
		
		return "";
	}
	
	/**
	 * FieldPrefix()
	 * @note HTML to place before the form field HTML
	 */
	protected function FieldPrefix() {
		return "";
	}
	
	/**
	 * FieldSuffix()
	 * @note HTML to place after the form field HTML
	 */
	protected function FieldSuffix() {
		return "";
	}

	/**
	 * Field()
	 * @note just returns the field. Note that the FileUploader.js handles all the HTML machinations, we just provide a container
	 */
	function Field($properties = array()) {
		$id = $this->id();
		$html = "";
		if($id == "") {
			$html .= "<p>No 'id' attribute was specified for the file upload field. File uploads cannot take place until you or your developer provides this information to UploadAnything</p>";
		} else {
			//set up the upload
			$html .= "<div class=\"uploadanything-upload-box field\"  id=\"{$id}\" rel=\"{$this->GetUploaderConfiguration()}\">Loading uploader...</div>";
		}
		return $html;
	}
	
	/**
	 * FieldHolder()
	 * @note returns the form field
	 * @returns string
	 */
	public function FieldHolder($properties = array()) {
		$this->LoadAssets();
		
		$gallery = $this->GetGalleryImplementation();
		
 		$reload = $this->AdminLink('ReloadList', $gallery->ID);
 		$resort = $this->AdminLink('SortItem', $gallery->ID);
 		
		$Title = (!empty($gallery->Title) ? $gallery->Title : "Un-named gallery");
		$Message = $this->XML_val('Message');
		$MessageType = $this->XML_val('MessageType');
		$RightTitle = $this->XML_val('RightTitle');
		$Type = $this->XML_val('Type');
		$extraClass = $this->extraClass;
		$Name = $this->XML_val('Name');
		$Field = $this->XML_val('Field');
		
		// Only of the the following titles should apply
		$titleBlock = "<div class=\"help\"><div class=\"inner\">";
		$titleBlock .= "<h4>{$Title}</h4>";
		$titleBlock .= "<label class=\"left\" for=\"{$this->id()}\">";
		$titleBlock .= "<ul><li><a href=\"{$reload}\" class=\"reload reload-all\">Reload</a><a class=\"sortlink\" href=\"{$resort}\">sort</a></li>";
		$titleBlock .= "<li><span>Max. file size: " . round($this->GetMaxSize() / 1024 / 1024, 2) . "Mb</span></li>";
		$titleBlock .= "<li><span>File types: " . $this->GetAllowedFilesNote() . "</span></li></ul>";
		$titleBlock .= "</label></div></div>";
		
		// $MessageType is also used in {@link extraClass()} with a "holder-" prefix
		$messageBlock = (!empty($Message)) ? "<span class=\"message $MessageType\">$Message</span>" : "";
		
		return <<<HTML
<div class="file-uploader">
	<div id="$Name" class="field ss-gridfield $Type $extraClass">
			{$titleBlock}
			<div class="middleColumn">
				$Field
				{$this->FieldPrefix()}
				{$this->FieldSuffix()}
				<div class="break"></div>
			</div>
	</div>
</div>
HTML;
	}
	
	
	/**
	 * UpdateCurrentRecord()
	 * @note handles replacement of the current record, if  ReplaceCurrent() is set with a valid file belonging to the current gallery
	 */
	protected function UpdateCurrentRecord($uploadDirectory, $filename) {
		
		/*
		
		//just replacing the file - do this and return
		$filename_path_current = BASE_PATH . '/' . $this->relatedDataObject->Filename;
		
		$query = "UPDATE File SET Name='" . Convert::raw2sql($filename) . "', Filename='" . Convert::raw2sql($uploadDirectory . "/" . $filename) . "' WHERE ID = " . Convert::raw2sql($this->relatedDataObject->ID);
		
		$update = DB::query($query);
		if($update) {
			if(is_writable($filename_path_current)) {
				//remove the old file
				unlink($filename_path_current);
			}
			$this->returnValue = array('success'=>true);
			return TRUE;
		} else {
			throw new Exception("Failed to replace the current file with your upload.");
		}
		
		*/
	}
	
	//return the file list for this gallery
	public function ReloadList(DisplayAnythingGallery $gallery) {
		if($gallery) {
			return $gallery->GetFileList($this);
		} else {
			return "";
		}
	}
	
	public function SortItem(DisplayAnythingGallery $gallery) {
		if($gallery) {
			return $gallery->SortItem();
		} else {
			return "";
		}
	}
	
	
	//TODO -  delete permissions, does file belong to gallery
	public function DeleteFile(UploadAnythingFile $file) {
		try {
			if($file) {
				if($result = $file->delete()) {
					return 1;
				}
			}
		} catch (Exception $e) {
		}
		return 0;
	}
	
	public function EditForm(UploadAnythingFile $file, $controller) {
		try {
			if($file) {
				//requirements...
				Requirements::css(FRAMEWORK_ADMIN_DIR . '/thirdparty/jquery-notice/jquery.notice.css');
				Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
				Requirements::css(FRAMEWORK_ADMIN_DIR .'/thirdparty/chosen/chosen/chosen.css');
				Requirements::css(THIRDPARTY_DIR . '/jstree/themes/apple/style.css');
				Requirements::css(FRAMEWORK_DIR . '/css/TreeDropdownField.css');
				Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/screen.css');
				Requirements::css(FRAMEWORK_DIR . '/css/GridField.css');
				
				//construct a form, return it
				$fields = $file->getCMSFields();
				
				$saveAction = new FormAction('doEdit', _t('UploadField.DOEDIT', 'Save'));
				$saveAction->addExtraClass('ss-ui-action-constructive icon-accept');
				$actions = new FieldList($saveAction);
				
				$validator = NULL;
				
				$form = new Form(
					$controller,//field
					'EditFile',
					$fields,
					$actions,
					$validator
				);
				$form->loadDataFrom($this);
				
				$result =  $this->customise(array(
					'Form' => $form
				))->renderWith('UploadAnythingFileEdit');
				
				return $result;
			}
		} catch (Exception $e) {
		}
		return 0;
	}
	
	/**
	* Upload
	* @note handles a single file upload to $uploadPath. Saves the file and links it to the dataobject, returning the saved file ID. If an error occurs an exception is thrown, causing the correct returnValue to be set.
	* @param $return if TRUE Upload will return a value rather than exit - which is the default for XHR uploads (after printing a JSON string)
	* @returns mixed
	* @note $this->file refers to the Upload handler instance dealing with a file that has been uploaded, not a Silverstripe {@link File} object
	*/
	final public function Upload(DisplayAnythingGallery $gallery) {
		try {
			
			//current member if they can upload, throws an exception and bails if not
			$member = $this->CanUpload();
			
			//set this->file to the correct handler
			$this->LoadUploadHandler();
			
			//if not set, create a target location
			if(!($this->target_location = $gallery->GetUploadTargetLocation())) {
				$this->target_location = "Uploads";//default
			}
		
			//final location of file
			$targetDirectory = "/" . trim($this->target_location, "/ ");
			$uploadPath = "/" . trim(ASSETS_PATH, "/ ") . $targetDirectory;
			$uploadDirectory = ASSETS_DIR . $targetDirectory;
			
			if(!is_writable(ASSETS_PATH)) {
				throw new Exception("Server error. This site's assets directory is not writable.");
			}
		
			if(!file_exists($uploadPath)) {
				mkdir($uploadPath, $this->directory_permission, TRUE);
			}
			
			if (!is_writable($uploadPath)){
				throw new Exception("Server error. Upload directory '{$uploadDirectory}' isn't writable.");
			}
			
			if (!$this->file){
				throw new Exception('No file handler was defined for this upload.');
			}
			
			$this->CheckAllowedSize();
			
			$this->CheckAllowedType();
			
			//now save the file, at this this point we aren't dealing with the File object, just an upload
			$target = $this->SetUploadFileName($uploadPath, $this->overwrite_file);
			
			//saves the file to the target directory
			$this->file->save($target);
			
			//here ? then the file save to disk has worked
			
			try {
				//catch some internal nerdy errors here so they don't bubble up
				
				$filename =  $this->GetFileName();
				
				//make a folder record (optionally makes it on the file system as well, although this is done in this->file->save()
				$folder = Folder::find_or_make($targetDirectory);//without ASSETS_PATH !
				if(empty($folder->ID)) {
					$this->UnlinkFile($target);
					throw new Exception('No folder could be assigned to this file');
				}
				
				//create a file and save it
				$file = new DisplayAnythingFile();
				$file->Name = $filename;
				$file->Title = $filename;
				//$file->Filename = $filename;//required?
				$file->ShowInSearch = 0;
				$file->ParentID = $folder->ID;
				$file->OwnerID = $member->ID;
				$file->GalleryID = $gallery->ID;//link it to the owner gallery
				//write the file
				$id = $file->write();
				if(!$id) {
					throw new Exception("The file '{$filename}' could not be saved (2).");
				}
			} catch (Exception $e) {
				$this->UnlinkFile($target);
				throw new Exception("The file could not be uploaded. File save failed with error: " . $e->getMessage());
			}
			
			//here ? no exceptions were thrown
			$this->returnValue = array('success'=>true);
			
		} catch (Exception $e) {
			$this->returnValue = array('error' => $e->getMessage());
		}
		
		//trigger a JSON return value
		return $this->UploadResult();
	}

}
?>
<?php
/**
  * DisplayAnythingGallery()
  * @note contains many DisplayAnythingFile()s
  * @note gallery migration from ImageGallery (v2) has been removed in this SS3.0 version
 */
class DisplayAnythingGallery extends DataObject {

	//for admin thumbs
	private $resize_method = "CroppedImage";
	private $thumb_width = 120;
	private $thumb_height = 120;
	
	private $target_location = "";//where files will be uploaded to
	
	//default file and directory permissions
	//if your web server runs as a specific user, these can be altered to make the rw for only that user
	protected static $file_permission = 0640;//0600 - only by the user and root
	protected static $directory_permission = 0750;//0700 - only by the user and root
	
	private $upload_handler;
	private $fileKey = 'qqfile';
	private $allowed_file_types = array();//an associative array, key is the mime type, value is the extension without a .
	private $overwrite_file = FALSE;
	
	private $returnValue = array();
	private $upload_file_name = "";
	
	static $db = array(
		'Title' => 'varchar(255)',
		'Description' => 'text',
		'Visible' => 'boolean',
		'GalleryFilePath' => 'varchar(255)',//currently unused - where files in this gallery are stored on disk, if left empty, the field will automatically determine this
		'Migrated' => 'boolean',//this is set when the gallery migration is complete
		//options for this gallery
		'ExtraMimeTypes' => 'text',//list of extra mimetypes for this gallery
		//In the future other config items for the gallery can go here
	);
	
	static $has_one = array(
		'Usage' => 'DisplayAnythingGalleryUsage',
	);
	
	static $has_many = array(
		'GalleryItems' => 'DisplayAnythingFile'
	);
	
	static $defaults = array(
		'Visible' => 0,
		'Migrated' => 0,
	);
	
	/**
	 * OrderedGalleryItems()
	 * @note return gallery items ordered as set in admin that are marked visible
	 */
	public function OrderedGalleryItems($only_visible = 1, $limit = NULL) {
		if(($only_visible && $this->Visible == 1) || !$only_visible) {
			return DataObject::get('DisplayAnythingFile','GalleryID=' . $this->ID, '"DisplayAnythingFile"."Sort" ASC, "File"."Created" DESC', '', $limit);
		}
		return FALSE;
	}
	
	/**
	 * OrderedFirstGalleryItem()
	 * @note returns the first gallery item, with ordering in mind, only.
	 */
	public function OrderedFirstGalleryItem($only_visible = 1) {
		return $this->OrderedGalleryItems($only_visible, 1);
	}
	
	/**
	 * GetFileList()
	 * @returns string
	 */
	public function GetFileList() {
		$html = "";
		//the related dataobject has many files
		if($files = $this->OrderedGalleryItems(FALSE)) {
			foreach($files as $file) {
				$html .= $this->GetFileListItem($file);
			}
		}
		if($html == "") {
			return $this->GetEmptyList();
		} else {
			return $html;
		}
	}
	
	/**
	 * @returns string
	 * @note returns the string used to denote an empty file list
	 */
	public function GetEmptyList() {
		return "<div class=\"file-uploader-item\"><p class=\"nada\">No files have been associated yet...</p></div>";
	}
	
	/**
	 * GetFileListItem()
	 * @note returns an HTML string representation of one gallery item
	 * @note in gallery mode /admin/EditForm/field/ImageGallery/item/1/ DetailForm/field/GalleryItems/item/78/edit?SecurityID=xyz
	 * @note in single mode /admin/EditForm/field/FeatureImage/item/80/edit?SecurityID=xyz
	 * DeleteLink
	 * @note in gallery mode: /admin/EditForm/field/ImageGallery/item/1/DetailForm/field/GalleryItems/item/78/delete?SecurityID=xyz
	 * @note in single mode /admin/EditForm/field/FeatureImage/item/80/delete?SecurityID=xyz
	 * @returns string
	 * @param $file a DisplayAnythingFile object or a class extending it
	 */
	protected function GetFileListItem(DisplayAnythingFile $file) {
		$html = "";
		
		$deletelink = DisplayAnythingAssetAdmin::AdminLink('DeleteFile', $file->ID);
		$editlink = DisplayAnythingAssetAdmin::AdminLink('EditFile', $file->ID);
		
		$html .= "<div class=\"file-uploader-item\" rel=\"{$file->ID}\">";
		//ss-ui-dialog-link
		$html .= "<a class=\"editlink\" href=\"{$editlink}\" title=\"" . htmlentities($file->Name, ENT_QUOTES) . "\">";
		
		
		//try to create a thumb (if it is one)
		$path = BASE_PATH . "/" . $file->Filename;
		
		$is_image = $file->IsImage($path);
		
		$thumb = "[no file found]";
		
		if(!file_exists($path)) {
			$thumb = "<br />File does not exist<br />";
		} else if($is_image) {
			$tag = $file->Thumbnail($this->resize_method, $this->thumb_width, $this->thumb_height);
			if($tag) {
				$thumb = $tag;
			} else {
				$thumb = "<img src=\"" . SAPPHIRE_DIR . "/images/app_icons/image_32.gif\" width=\"24\" height=\"32\" alt=\"unknown image\" /><br />(no thumbnail)";
			}
		} else {
			//TODO: get a nice file icon...
			$thumb = DisplayAnythingFile::GetFileIcon();
		}
		
		$html .= "<div class=\"thumb\">{$thumb}</div>";
		$html .= "<div class=\"caption\"><p>" . substr($file->Title, 0, 16)  . "</p></div>";
		$html .= "</a>";
		$html .= "<div class=\"tools ui-state-default\">";
		$html .= "<a class=\"deletelink ui-icon btn-icon-decline\" href=\"{$deletelink}\"></a>";
		$html .= "<img src=\"" . rtrim(Director::BaseURL(), "/") . "/display_anything/images/sort.png\" title=\"drag and drop to sort\" class=\"sortlink\" alt=\"drag and drop to sort\" />";
		$html .= "</div>";
		$html .= "</div>";
		
		return $html;
	}
	
	/**
	 * SortItem()
	 * @note HTTP POST API to update sort order in a gallery, returns the number of sorted items
	 * @note we don't plug into the ORM here to make for faster uploading
	 * @return integer
	 * @todo match against gallery, sanity checks on POST
	 */
	final public function SortItem() {
		$success = 0;
		if(!empty($_POST['items']) && is_array($_POST['items'])) {
			foreach($_POST['items'] as $item) {
				if(!empty($item['id'])) {
					$sort = (isset($item['pos']) ? (int)$item['pos'] : 0);
					//run a quick query and bypass the ORM
					$query = "UPDATE \"DisplayAnythingFile\" SET Sort = '" . Convert::raw2sql($sort) . "' WHERE ID = '" . Convert::raw2sql($item['id']) . "'";
					//print $query . "\n";
					$result = DB::query($query);
					if($result) {
						$success++;
					}
				}
			}
		}
		return $success;
	}
	
	/**
	 * GetUploadTargetLocation()
	 * @note get the target location on the file system for uploads into this gallery, a subdirectory of ASSETS_PATH
	 */
	public function GetUploadTargetLocation() {
		return "display-anything/gallery/" .  ceil($this->ID / 1000) . "/" . $this->ID . "/";
	}

	/**
	 * CanUpload()
	 * @note can the current member upload ?
	 * @todo more upload permissions based on SS Permission backend & make configurable
	 * @returns Member
	 */
	protected function CanUpload() {
	
		$can = ini_get('file_uploads');
		if(!$can) {
			throw new Exception('File uploads are not enabled on this system. To enable them, get your administrator to set file_uploads=1 in php.ini');
		}
	
		$member = Member::currentUser();
		if(empty($member->ID)) {
			throw new Exception("You must be signed in to upload files");
		}
		return $member;
	}
	
	/**
	 * @returns octal
	 * @note returns file permission
	 */
	public function GetFilePermission() {
		return self::$file_permission;
	}
	
	/**
	 * @returns octal
	 * @note returns file permission
	 */
	public function GetDirectoryPermission() {
		return self::$directory_permission;
	}
	
	/**
	 * LoadUploadHandler()
	 * @note loads the correct handler depending on incoming data - if $_FILES is present, use a form post controller, if the XHR request key is present as a _GET variable, use the XHR controller
	 * @returns object
	 */
	final protected function LoadUploadHandler() {
		if (isset($_GET[$this->fileKey])) {
			//this is our trig
			$this->upload_handler = new Uploader_XHRSubmission($this->fileKey, $this);
			$this->upload_handler->saveToTmp();//saves raw stream to tmp location
		} elseif (isset($_FILES[$this->fileKey])) {
			$this->upload_handler = new Uploader_PostedForm($this->fileKey, $this);
		} else {
			throw new Exception("No upload handler was defined for this request");
		}
	}
	
	/**
	 * GetUploadMaxSize() gets the maximum allowable file upload size
	 * @returns int
	 */
	public static function GetUploadMaxSize() {
		return self::ToBytes(ini_get('upload_max_filesize'));
	}

	/**
	 * GetPostMaxSize() gets the maximum allowable POST size
	 * @returns int
	 */
	public static function GetPostMaxSize() {
		return self::ToBytes(ini_get('post_max_size'));
	}
	
	/**
	 * GetMaxSize() gets the maximum allowable upload size
	 * @returns int
	 */
	public function GetMaxSize() {
		//returns whatever is the minimum allowed upload size - out of FILE or POST
		return min( array( self::GetUploadMaxSize(), self::GetPostMaxSize() ) );
	}
	
	/**
	 * ToBytes()
	 * @note takes a string like 1G, 100M or 1200K and converts it to bytes value
	 * @returns int
	 * @param $str string
	 */
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
	
	/**
	 * CheckAllowedSize() check size of upload against configured size
	 * @returns boolean
	 * @throws Exception
	 */
	final private function CheckAllowedSize() {
		$size = $this->upload_handler->getSize();
		
		if ($size == 0) {
			throw new Exception('File is empty');
		}
		
		$postSize = self::GetPostMaxSize();
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
	 * @returns boolean
	 * @throws Exception
	 */
	final private function CheckAllowedType() {
	
		if(empty($this->allowed_file_types)) {
			throw new Exception("No allowed file types have been defined - for security reasons this file cannot be uploaded.");
		}
		
		$allowed_list = "";
		foreach($this->allowed_file_types as $type=>$ext) {
			$allowed_list .= $type . ($ext ? " (" .strtolower($ext) . ")" : "") . ", ";
		}
		
		$mimeType = strtolower($this->upload_handler->getMimeType());
		if(!array_key_exists($mimeType, $this->allowed_file_types)) {
			throw new Exception("This file uploader does not allow files of type '{$mimeType}' to be uploaded. Allowed types: " .  trim($allowed_list, ", ") . ".");
		}
		return TRUE;
	}
	
	final public function GetAllowedFileTypes() {
		return $this->allowed_file_types;
	}
	
	/**
	 * WebImageMimeTypes()
	 * @returns array
	 * @note returns a mimetype map for common image file types
	 */
	final private function DefaultMimeTypes() {
		return array(
			'image/jpg' => 'jpg',
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/x-png' => 'png',//IE sometimes uploads older pre standardized PNG files as this mimetype. Another feather in its c(r)ap
			'image/gif' => 'gif',
			'image/pjpeg' => 'jpg',
		);
	}
	
	final private function DefaultMimeTypesTitle() {
		return "Default - image gallery";
	}
	
	final public function DefaultMimeTypesList() {
		return $this->DefaultMimeTypesTitle() . " (" . implode(", ", array_unique(array_keys($this->DefaultMimeTypes()))) . ")";
	}
	
	/**
	 * WebImageMimeTypes()
	 * @returns array
	 * @note returns a mimetype map for common image file types
	 * @deprecated
	 */
	final private function WebImageMimeTypes() {
		return $this->DefaultMimeTypes();
	}
	
	// -- START CONFIG
	
	/**
	 * SetMimeTypes()
	 * @note by default we just allow images
	 * @param $mime_types pass in mimetype=>extension pairs to override or a string/of,mime/types
	 * @note are you unsure about mimetypes ? http://www.google.com.au/search?q=mimetype%20list
	 * <blockquote>So, why doesn't this use file extensions? File Extensions are part of the file name and have no bearing on the contents of the file whatsoever. 'Detecting' file content by matching the characters after the last "." may provide a nice string to work with but in fact lulls the developer into a false sense of security.
	 		To test this, create a new PHP file, add '<?php print "I am PHP";?>' and save that file as 'image.gif' then upload it using an uploader that allows file Gif files. What happens when you browse to that file ?
	 		DisplayAnything requires your developer (or you) to provide it a map of allowed mimetypes. Keys are mimetypes, values are a short blurb about the file. By default it uses the standard mimetypes for JPG, GIF and PNG files.
	 		If you are uploading a valid file and the DisplayAnything MimeType checker is not allowing it, first determine its mimetype and check against the whitelist you have provided. Some older software will save a file in older, non-standard formats.
	 		DisplayAnything uses the 'type' value provided by the PHP file upload handler for standard uploads that populate $_FILES. For XHR uploads, DisplayAnything uses finfo_open() if available, followed by an image mimetype check, followed by mime_content_type (deprecated). If no mimeType can be detected. DisplayAnything refuses the upload, which is better than allowing unknown files on your server.
	 		
	 		If you are using the DisplayAnything file gallery manager, the Usage tab provides a method of managing allowed file types on a per gallery basis.
	 		</blockquote>
	 */
	final public function SetMimeTypes($mime_types = NULL) {
		
		//set from possible gallery usage
		$usage = FALSE;
		$this->allowed_file_types = array();
		if($usage = $this->Usage()) {
			$mime_types = $usage->MimeTypes;
		}
		
		$types_list = array();
		
		if(is_string($mime_types)) {
			$chars = DisplayAnythingGalleryUsage::splitterChars();
			
			//allowed
			$mime_types = array_flip(preg_split("/[" . preg_quote($chars) . "]+/", trim($mime_types, $chars)));
			
			if($map = Config::inst()->get('HTTP', 'MimeTypes')) {
				$web_image_mime_types = $this->WebImageMimeTypes();
				foreach($mime_types as $mime_type=>$value) {
					//framework/email/Mailer.php
					$extensions = array_keys($map, $mime_type);
					if(!empty($extensions)) {
						foreach($extensions as $extension) {
							$types_list[$mime_type][] = $extension;
						}
					}
					if(array_key_exists($mime_type, $web_image_mime_types)) {
						//the known list of web image types, if in the list
						$types_list[$mime_type][] = $web_image_mime_types[$mime_type];
					}
				}
			}
		}
		
		foreach($types_list as $mime_type => $extensions) {
			$this->allowed_file_types[$mime_type] = implode(",", $extensions);
		}
		
		//fall back to basics
		if(empty($this->allowed_file_types)) {
			//nothing set, assume image upload for starters
			$this->allowed_file_types = $this->WebImageMimeTypes();
		}
		
		return $this;
	}
	
	/**
	 * SetTargetLocation()
	 * @param $location a subdirectory of the SS ASSETS_PATH
	 * @note this is optional, if not set the Gallery will determine the path (and is recommended to leave it this way)
	 * @returns DisplayAnythingGallery
	 */
	final public function SetTargetLocation($location) {
		$this->target_location = $location;
		return $this;
	}
	
	/**
	 * SetFileKey()
	 * @note allow override of filekey used in upload, allows file replacement to hook into Upload()
	 * @note optional, will default to the property setting
	 * @returns DisplayAnythingGallery
	 */
	public function SetFileKey($fileKey) {
		if($fileKey == '') {
			throw new Exception("File key cannot be empty");
		}
		$this->fileKey = $fileKey;
		return $this;
	}
	
	/**
	 * SetPermissions()
	 * @note allows you to set permissions for file and directory creation from a _config.php file (e.g DisplayAnythingGallery::SetPermissions(0600,0700)
	 * @returns void
	 */
	public static function SetPermissions($file = 0640, $directory = 0750) {
		self::$file_permission = $file;
		self::$directory_permission = $directory;
	}
	
	/**
	 * OverwriteFile()
	 * @note sets the file field to overwrite if a same-named file is found, this is a configuration item that returns this field to enable chainable setups
	 * @returns DisplayAnythingGallery
	 * @todo retrieve setting from Usage
	 */
	final public function OverwriteFile($replace = FALSE) {
		$this->overwrite_file = $replace;
		return $this;
	}
	
	//--- END CONFIG
	
	/**
	 * GetFileName()
	 * @returns string
	 */
	public function GetFileName() {
		return $this->upload_file_name;
	}
	
	/**
	 * @note this is a compatibility function with File::setName() - as we aren't dealing with a File object at this point, we can't (and don't want to) use File::setName() as this needs a File instance.
	 * @note in SS3.0+, FileNameFilter abstracts this cleaning away, unlike the SS2.4 which handles cleaning within File::setName
	 * @param $name a raw file name without a file extension
	 * @returns string
	 */
	protected function CleanFileName($name) {
		$filter = Object::create('FileNameFilter');
		$filter::$default_use_transliterator = false;
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
		$pathinfo = pathinfo($this->upload_handler->getName());
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
	
	/**
	 * UnlinkFile() unlinks a target file based on a path
	 * @returns boolean
	 * @todo security around unlinking any path ... :?
	 * @deprecated use DisplayAnythingAssetAdmin::DeleteFile()
	 */
	private function UnlinkFile($target) {
		if(is_writable($target)) {
			unlink($target);
		}
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
	* Upload
	* @note handles a single file upload to $uploadPath. Saves the file and links it to the dataobject, returning the saved file ID. If an error occurs an exception is thrown, causing the correct returnValue to be set.
	* @param $return if TRUE Upload will return a value rather than exit - which is the default for XHR uploads (after printing a JSON string)
	* @returns mixed
	* @note $this->file refers to the Upload handler instance dealing with a file that has been uploaded, not a Silverstripe {@link File} object
	*/
	final public function Upload() {
		try {
			
			//current member if they can upload, throws an exception and bails if not
			$member = $this->CanUpload();
			
			//set this->upload_handler to the correct handler
			$this->LoadUploadHandler();
			
			//if not set, create a target location
			if(!($this->target_location = $this->GetUploadTargetLocation())) {
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
				mkdir($uploadPath, self::$directory_permission, TRUE);
			}
			
			if (!is_writable($uploadPath)){
				throw new Exception("Server error. Upload directory '{$uploadDirectory}' isn't writable.");
			}
			
			//this->file is the upload handler, not the File object
			if (!$this->upload_handler) {
				throw new Exception('No file handler was defined for this upload.');
			}
			
			$this->CheckAllowedSize();
			
			$this->CheckAllowedType();
			
			//now save the file, at this this point we aren't dealing with the File object, just an upload
			$target = $this->SetUploadFileName($uploadPath, $this->overwrite_file);
			
			//saves the file to the target directory
			$this->upload_handler->save($target);
			
			//here ? then the file save to disk has worked
			
			try {
				//catch some internal nerdy errors here so they don't bubble up
				
				$filename =  $this->GetFileName();
				
				//make a folder record (optionally makes it on the file system as well, although this is done in this->upload_handler->save()
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
				$file->GalleryID = $this->ID;//link it to the owner gallery
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
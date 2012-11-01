<?php
/**
 * DisplayAnythingGalleryField()
 * @note provides a gallery configuration and viewer field in the CMS for a DisplayAnythingGallery
 * @package display_anything
 * @copyright Codem 2011 onwards
 * @author <a href="http://www.codem.com.au">Codem / James Ellis</a>
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
 * @todo use bundled jQuery uploader
 */
class DisplayAnythingGalleryField extends FormField {
	
	private $configuration = array();//configuration for the JS upload client
	public $show_help = TRUE;//FALSE to not show Upload Help text in the gallery
	protected $gallery;
	
	/**
	 * @param $gallery DisplayAnythingGallery
	 * @param $name field name
	 * @param $title field title
	 */
	public function __construct($name, $title = "", DisplayAnythingGallery $gallery) {
		parent::__construct($name, $title);
		$this->gallery = $gallery;
		$this->gallery->SetMimeTypes();
	}
	
	/**
	 * GetGalleryImplementation()
	 * @note use this function to get the current gallery implementation
	 * @returns DisplayAnythingGallery
	 * @throws Exception
	 */
	final protected function GetGalleryImplementation() {
		return $this->gallery;
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
			throw new Exception('Incorrect configuration for DisplayAnythingGalleryField');
		}
		$this->configuration = $configuration;
		return $this;
	}
	
	/**
	 * GetAllowedExtensions() used solely for client side validation on the filename
	 * @returns array
	 * @throws Exception
	 */
	final private function GetAllowedExtensions() {
		$types = $this->gallery->GetAllowedFileTypes();
		if(empty($types)) {
			throw new Exception("No allowed file types have been defined for this uploader.");
		}
		return array_unique(array_values($types));
	}

	/**
	 * @returns string
	 */
	protected function GetAllowedFilesNote() {
		return $this->GetGalleryImplementation()->Usage()->TitleMap();
	}
	
	/**
	 * FieldPrefix()
	 * @note HTML to place before the form field HTML
	 */
	public function FieldPrefix() {
		return "";
	}
	
	/**
	 * FieldSuffix()
	 * @note HTML to place after the form field HTML
	 */
	public function FieldSuffix() {
		$gallery = $this->GetGalleryImplementation();
		$list = $gallery->GetFileList();
		$html = "<div class=\"file-uploader-list field\">{$list}</div>";
		if($this->show_help) {
			$html .= $this->FieldHelp();
		}
		return $html;
	}
	
	protected function FieldHelp() {
		return "<div class=\"help help-under\"><div class=\"inner\">"
					. " <h4>Upload help</h4><ul>"
					. " <li><strong>Chrome</strong>, <strong>Safari</strong> and <strong>Firefox</strong> support multiple image upload (Hint: 'Ctrl/Cmd + click' to select multiple images in your file chooser)</li>"
					. "<li>In <strong>Firefox</strong>, <strong>Safari</strong> and <strong>Chrome</strong> you can drag and drop images onto the upload button</li>"
					. "<li>Internet Explorer <= 9 does not support multiple file uploads or drag and drop of files. Click the 'Upload a file' button to choose a file.</li>"
					. "</ul>"
					. "</div></div>";
	}
	
	/**
	 * Field()
	 * @note just returns the field. Note that the FileUploader.js handles all the HTML machinations, we just provide a container
	 * @param $properties
	 */
	public function Field($properties = array()) {
		$id = $this->id();
		$html = "";
		if($id == "") {
			$html .= "<p>No 'id' attribute was specified for the file upload field. File uploads cannot take place until you or your developer provides this information.</p>";
		} else {
			//set up the upload
			$html .= "<div class=\"uploader-upload-box field\"  id=\"{$id}\" rel=\"{$this->GetUploaderConfiguration()}\">Loading uploader...</div>";
		}
		return $html;
	}
	
	/**
	 * FieldHolder()
	 * @param $properties
	 */
	public function FieldHolder($properties = array()) {
	
		$fields = new FieldList(array(new TabSet('Root')));
		$fields->addFieldToTab('Root', new Tab($this->name .'Files', 'Files'));
		$fields->addFieldToTab('Root', new Tab($this->name .'Details', 'Details'));
		$fields->addFieldToTab('Root', new Tab($this->name .'Usage', 'Usage'));
		
		$gallery = $this->GetGalleryImplementation();
		
		$id = $gallery->ID;
		
		$this->LoadAssets();
		
		$fields->addFieldsToTab(
			"Root.{$this->name}Details",
			array(
				new TextField("{$this->name}[{$id}][Title]","Title", $gallery->getField('Title')),
				new TextareaField("{$this->name}[{$id}][Description]","Description", $gallery->getField('Description')),
				new CheckboxField("{$this->name}[{$id}][Visible]","Publicly Visible", $gallery->getField('Visible') == 1 ?  TRUE : FALSE),
			)
		);
		
		$picker = new DropDownField(
			"{$this->name}[{$id}][UsageID]",
			"",
			DataObject::get('DisplayAnythingGalleryUsage')->map('ID','TitleMap'),
			$gallery->getField('UsageID'),
			NULL,
			'Add new'
		);
		$picker->addExtraClass('usage_picker');
		
		$usage_id = new HiddenField("GalleryUsage[{$this->name}][{$id}][ID]");
		$usage_id->addExtraClass('usage_id');
		$usage_title = new TextField("GalleryUsage[{$this->name}][{$id}][Title]","Title");
		$usage_title->addExtraClass('usage_title');
		$usage_mimetypes = new TextareaField("GalleryUsage[{$this->name}][{$id}][MimeTypes]","Allowed Mimetypes");
		$usage_mimetypes->addExtraClass('usage_mimetypes');
		
		$fields->addFieldsToTab(
			"Root.{$this->name}Usage",
			array(
				new LiteralField("GalleryUsageBoxStart", "<div class=\"display_anything display_anything_usage\">"),
				new HeaderField("GalleryUsagePicker","Current gallery usage", 4),
				$picker,
				new HeaderField("GalleryUsageEntry","Enter new usage or choose a current one to edit", 4),
				$usage_id, $usage_title, $usage_mimetypes,
				new LiteralField("GalleryUsageBoxEnd", "</div>"),
			)
		);
		
		//the actual gallery field, using the parent field to render
		$html = "<div class=\"display_anything_field upload_anything_field\">";
		//this container is used to determine child-parent relationship in display.js
		if(!empty($id)) {
			
			$reload = DisplayAnythingAssetAdmin::AdminLink('ReloadList', $gallery->ID);
			$resort = DisplayAnythingAssetAdmin::AdminLink('SortItem', $gallery->ID);
			
			$Title = (!empty($gallery->Title) ? $gallery->Title : "Un-named gallery");
			$Description = (!empty($gallery->Title) ? $gallery->Description : "No description provided");
			$Visible = $gallery->Visible == 1 ? "public" : "private";
			$Message = $this->XML_val('Message');
			$MessageType = $this->XML_val('MessageType');
			$RightTitle = $this->XML_val('RightTitle');
			$Type = $this->XML_val('Type');
			$extraClass = $this->extraClass;
			$Name = $this->XML_val('Name');
			$Field = $this->XML_val('Field');
			
			// Only of the the following titles should apply
			$titleBlock = "<div class=\"help\"><div class=\"inner\">";
			$titleBlock .= "<h4>" . htmlentities($Title, ENT_QUOTES, 'UTF-8') . " <span>(" . $Visible  . ")</span></h4>";
			$titleBlock .= ($Description ? "<p>" . htmlentities($Description, ENT_QUOTES, 'UTF-8') . "</p>" : "");
			$titleBlock .= "<label class=\"left\" for=\"{$this->id()}\">";
			$titleBlock .= "<ul><li><a href=\"{$reload}\" class=\"reload reload-all\">Reload</a><a class=\"sortlink\" href=\"{$resort}\">sort</a></li>";
			$titleBlock .= "<li><span><strong>Max. file size:</strong> " . round($gallery->GetMaxSize() / 1024 / 1024, 2) . "Mb</span></li>";
			$titleBlock .= "<li><span><strong>File types:</strong> " . $this->GetAllowedFilesNote() . "</span></li></ul>";
			$titleBlock .= "</label></div></div>";
			
			// $MessageType is also used in {@link extraClass()} with a "holder-" prefix
			$messageBlock = (!empty($Message)) ? "<span class=\"message $MessageType\">$Message</span>" : "";
		
			$html .= <<<HTML
<div class="file-uploader">
	<div id="$Name" class="field $Type $extraClass">
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
		} else {
			$html .= "<div class=\"message notice\"><p>Gallery items can be uploaded after the gallery is saved for the first time</p></div>";
		}
		$html .= "</div>";
		
		$fields->addFieldsToTab(
			"Root.{$this->name}Files",
			array(
				new LiteralField('DisplayAnythingGalleryField', $html)
			)
		);
		
		$html = "";
		foreach($fields as $field) {
			$html .= $field->FieldHolder();
		}
		return $html;
	}
	
	/**
	 * SaveUsage()
	 * @note saves gallery usage info
	 * @param $id the gallery id
	 * @return boolean
	 */
	protected function SaveUsage($id) {
		if(!empty($_POST['GalleryUsage'][$this->name][$id]['Title'])) {
			//adding a new gallery usage
			$usage = new DisplayAnythingGalleryUsage();
			$usage->Title = $_POST['GalleryUsage'][$this->name][$id]['Title'];
			$mimetypes = (!empty($_POST['GalleryUsage'][$this->name][$id]['MimeTypes']) ? $_POST['GalleryUsage'][$this->name][$id]['MimeTypes'] : '');
			
			//if one per line, replace with commas
			$mimetypes = preg_replace("/[\n\r\s]+/", ",", $mimetypes);
			$mimetypes = preg_replace("/[,]{2}/", ",", $mimetypes);
			$usage->MimeTypes = $mimetypes;
			
			if(!empty($_POST['GalleryUsage'][$this->name][$id]['ID'])) {
				$usage->ID = $_POST['GalleryUsage'][$this->name][$id]['ID'];
			}
			
			return $usage->write();
		}
		return FALSE;
	}
	
	/**
	 * saveInto()
	 * @note saves the current record
	 * @param $record
	 */
	public function saveInto(DataObjectInterface $record) {
	
		try {
	
			//save into the DisplayAnythingGallery
			if(!empty($_POST[$this->name]) && is_array($_POST[$this->name])) {
				$gallery = $record->{$this->name}();
				$migrate = FALSE;
				foreach($_POST[$this->name] as $id=>$data) {
				
					if($usage = $this->SaveUsage($id)) {
						$gallery->UsageID = $usage;
					} else if(!empty($data['UsageID'])) {
						$gallery->UsageID = $data['UsageID'];
					}
				
					if(!empty($data['MigrateImageGalleryAlbumID'])) {
						$migrate = $data['MigrateImageGalleryAlbumID'];
					}
					
					if($id == 0 || $id == $gallery->ID) {
						//creating this gallery or updating it...
						$gallery->Title = !empty($data['Title']) ? $data['Title'] : '';	
						$gallery->Description = !empty($data['Description']) ? $data['Description'] : '';	
						$gallery->Visible = !empty($data['Visible']) ?  1 : 0;
						$gallery->Migrated = !empty($data['Migrated']) ?  1 : 0;
						if($id = $gallery->write()) {
							$relation_field = $this->name . "ID";
							$record->$relation_field = $id;
						} else {
							throw new Exception("Could not save gallery '{$gallery->Title}'");
						}
						break;
					}
				}
			}
		} catch (Exception $e) {
		}
	}
	
	/**
	 * LoadScript()
	 * @note override this method to use your own CSS. Changing this may break file upload layout.
	 */
	public static function LoadScript() {
		
		//have to use bundled jQ or CMS falls over in a screaming heap
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-ui/jquery-ui.min.js");
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
	 * @see "### Options of both classes ###" in the FileUploader client readme.md
	 */
	public function GetUploaderConfiguration() {
		try {
		
			$gallery = $this->GetGalleryImplementation();
		
			//work out the upload location.
			$this->configuration['action'] = DisplayAnythingAssetAdmin::AdminLink('Upload', $gallery->ID);
			$this->configuration['reload'] = DisplayAnythingAssetAdmin::AdminLink('ReloadList', $gallery->ID);
			
			if(!isset($this->configuration['params'])) {
				$this->configuration['params'] = array();
			}
			
			$this->configuration['allowedExtensions']  = $this->GetAllowedExtensions();
	
			//these options are not supported in all browsers
			$this->configuration['sizeLimit'] = $gallery->GetMaxSize();
			$this->configuration['minSizeLimit'] = 0;
			
			if(!isset($this->configuration['maxConnections'])) {
				$this->configuration['maxConnections'] = 3;
			}
			
			$string = htmlentities(json_encode($this->configuration), ENT_QUOTES, "UTF-8");
			return $string;
		} catch (Exception $e) {}
		
		return "";
	}
	
	public function EditForm(DisplayAnythingFile $file, $controller) {
		try {
			if($file) {
				//requirements...
				Requirements::css(FRAMEWORK_ADMIN_DIR . '/thirdparty/jquery-notice/jquery.notice.css');
				Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
				Requirements::css(FRAMEWORK_ADMIN_DIR .'/thirdparty/chosen/chosen/chosen.css');
				Requirements::css(THIRDPARTY_DIR . '/jstree/themes/apple/style.css');
				Requirements::css(FRAMEWORK_DIR . '/css/TreeDropdownField.css');
				Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/screen.css');
				Requirements::css("display_anything/css/display.css");
				
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
				$form->loadDataFrom($file);
				
				$form->setFormAction(DisplayAnythingAssetAdmin::AdminLink('EditFile', $file->ID));
				
				return $form;
			}
		} catch (Exception $e) {
		}
		return "";
	}
}
?>
<?php
/**
  * DisplayAnythingGallery()
  * @note contains many DisplayAnythingFile()s
 */
class DisplayAnythingGallery extends DataObject {

	//for admin thumbs
	private $resize_method = "CroppedImage";
	private $thumb_width = 120;
	private $thumb_height = 120;
	
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
		'Visible' => 1,
		'Migrated' => 0,
	);
	
	/**
	 * OrderedGalleryItems()
	 * @note return gallery items ordered as set in admin that are marked visible
	 */
	public function OrderedGalleryItems($only_visible = TRUE) {
		if(($only_visible && $this->Visible == 1) || !$only_visible) {
			return DataObject::get('DisplayAnythingFile','GalleryID=' . $this->ID, '"File"."Sort" ASC, "File"."Created" DESC');
		}
		return FALSE;
	}
	
	/**
	 * GetFileList()
	 * @returns string
	 */
	public function GetFileList(UploadAnythingField $field) {
		$html = "";
		//the related dataobject has many files
		$files = $this->OrderedGalleryItems();
		if($files) {
			foreach($files as $file) {
				$html .= $this->GetFileListItem($file, $field);
			}
		}
		return $html;
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
	 * @param $file an UploadAnythingFile object or a class extending it
	 * @param $relation one of self, single or gallery
	 */
	protected function GetFileListItem($file, UploadAnythingField $field) {
		$html = "";
		
		$deletelink = $field->AdminLink('DeleteFile', $file->ID);
		$editlink = $field->AdminLink('EditFile', $file->ID);
		
		$html .= "<div class=\"file-uploader-item\" rel=\"{$file->ID}\">";
		
		//$html .= "<a class=\"cms-panel-link\" data-target-panel=\".cms-content\" href=\"{$editlink}\" title=\"" . htmlentities($file->Name, ENT_QUOTES) . "\">";
		
		//$html .= "<a href=\"{$editlink}\" class=\"editlink\" title=\"" . htmlentities($file->Name, ENT_QUOTES) . "\">";
		
		$html .= "<a class=\"ss-ui-dialog-link editlink\" href=\"{$editlink}\" title=\"" . htmlentities($file->Name, ENT_QUOTES) . "\">";
		
		
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
			$thumb = UploadAnythingFile::GetFileIcon();
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
					$query = "UPDATE \"File\" SET Sort = '" . Convert::raw2sql($sort) . "' WHERE ID = '" . Convert::raw2sql($item['id']) . "'";
					print $query . "\n";
					$result = DB::query($query);
					if($result) {
						$success++;
					}
				}
			}
		}
		return $success;
	}
	
	//subdirectory of ASSETS_PATH
	public function GetUploadTargetLocation() {
		return "display-anything/gallery/" .  ceil($this->ID / 1000) . "/" . $this->ID . "/";
	}
}
?>
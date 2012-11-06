<?php
/**
 * Gallery usage configuration class
 */
class DisplayAnythingGalleryUsage extends DataObject {

	static $db = array(
		'Title' => 'Varchar(255)',
		'MimeTypes' => 'Text',
	);
	
	public function TitleMap() {
		$mimetypes = trim($this->MimeTypes, ", ");
		if($mimetypes != "") {
			$types = explode(",", $this->MimeTypes);
			if(!empty($types)) {
				$list = implode(", ", array_unique($types));
				return $this->Title . " (" . $list  . ")";
			}
		}
		return "";
	}
	
	public static $summary_fields = array(
		'Title' => 'Usage',
		'MimeTypes' => 'Allowed MimeTypes',
	);
	
	protected function defaultUsageRecords() {
		return array(
			array(
				'Title' => 'Image',
				'MimeTypes' => 'image/png,image/jpg,image/jpeg,image/gif',
			),
			array(
				'Title' => 'Documents',
				'MimeTypes' => 'text/plain',
			),
		);
	}
	
	public static function splitterChars() {
		return ",\n\r ";
	}
	
	/**
	 * requireDefaultRecords()
	 * @note seeds the usage table with some default 'gallery' types
	 * @todo if there is a less hackish way to do this I'm all ears
	 */
	public function requireDefaultRecords() {
		try {
			$query = "SELECT COUNT(ID) AS Records FROM DisplayAnythingGalleryUsage";
			if(($result = DB::Query($query)) && ($record = $result->current()) && ($record['Records'] == 0)) {
				$defaults = self::defaultUsageRecords();
				foreach($defaults as $default) {
					$usage = new DisplayAnythingGalleryUsage($default);
					$usage->write();
				}
				return TRUE;
			}
		} catch (Exception $e) {
		}
		return FALSE;
	}
}
?>
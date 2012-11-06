<?php
/**
 * Uploader_XHRSubmission
 * @note an XHR upload handler
 */
class Uploader_XHRSubmission {

	private $fileKey;
	private $tmp_location;
	private $tmp_handle;
	
	private $field;
	
	public function __construct($fileKey, $field) {
		$this->fileKey = $fileKey;
		$this->field = $field;
	}
	
	/**
	* Save the input stream to a path on disk
	* @return boolean TRUE on success
	*/
	public function saveToTmp() {
		$input = fopen("php://input", "r");
		$this->tmp_location = tempnam(sys_get_temp_dir(), 'xhr_upload_');
		$this->tmp_handle = fopen($this->tmp_location, "w");
		stream_copy_to_stream($input, $this->tmp_handle);
		fclose($input);
		return true;
	}
	
	//save file, at this point all checks have been done
	public function save($path) {
		if(file_exists($this->tmp_location)) {
			$result = rename($this->tmp_location, $path);
			if(!$result) {
				throw new Exception('Could not save uploaded file. Can the destination path be written to?');
			}
			@chmod($path, $this->field->GetFilePermission());
		} else {
			throw new Exception('Could not save uploaded file. The uploaded file no longer exists.');
		}
		return TRUE;
	}
	
	public function cleanup() {
		if(is_resource($this->tmp_handle)) {
			fclose($this->tmp_handle);
		}
		if(file_exists($this->tmp_location)) {
			unlink($this->tmp_location);
		}
	}
	
	public function getTmpFile() {
		return $this->tmp_location;
	}
	
	public function getName() {
		return $_GET[$this->fileKey];
	}
	public function getSize() {
		if(file_exists($this->tmp_location)) {
			return filesize($this->tmp_location);
		}
		return 0;
	}
	public function getMimeType() {
		$mimeType = DisplayAnythingFile::MimeType($this->tmp_location);
		if(!$mimeType['mimetype']) {
			throw new Exception("Cannot reliably determine the mime-type of this file");
		}
		return $mimeType['mimetype'];
	}

}
?>
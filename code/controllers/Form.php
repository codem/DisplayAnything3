<?php
/**
* Uploader_PostedForm
* @note Handle file uploads via regular form post (uses the $_FILES array)
*/
class Uploader_PostedForm {

	private $fileKey;
	private $field;
	
	public function __construct($fileKey, $field) {
		$this->fileKey = $fileKey;
		$this->field = $field;
	}
	
	/**
	* Save the file to the specified path
	* @return boolean TRUE on success
	*/
	function save($path) {
		if(!is_uploaded_file($_FILES[$this->fileKey]['tmp_name'])) {
			throw new Exception("The server did not allow this file to be saved as it does not appear to be a file that has been uploaded.");
		}
		if(!move_uploaded_file($_FILES[$this->fileKey]['tmp_name'], $path)){
			throw new Exception('Could not save uploaded file. Can the destination path be written to?');
		}
		
		@chmod($path, $this->field->GetFilePermission());
		return TRUE;
	}
	function getName() {
		return $_FILES[$this->fileKey]['name'];
	}
	function getSize() {
		return $_FILES[$this->fileKey]['size'];
	}
	//ignore the mimetype provided by _FILES, not trusted, allow the server to find it.
	function getMimeType() {
		$mimeType = DisplayAnythingFile::MimeType($_FILES[$this->fileKey]['tmp_name']);
		if(!$mimeType['mimetype']) {
			throw new Exception("Cannot reliably determine the mime-type of this file");
		}
		return $mimeType['mimetype'];
	}
}
?>
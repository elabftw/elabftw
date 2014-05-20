<?php
class BigUpload
{
	/**
	 * Temporary directory for uploading files
	 */
	const TEMP_DIRECTORY = '../files/tmp/';

	/**
	 * Directory files will be moved to after the upload is completed
	 */
	const MAIN_DIRECTORY = '../files/';

	/**
	 * Max allowed filesize. This is for unsupported browsers and
	 * as an additional security check in case someone bypasses the js filesize check.
	 *
	 * This must match the value specified in main.js
	 */
	const MAX_SIZE = 2147483648;

	/**
	 * Temporary directory
	 * @var string
	 */
	private $tempDirectory;

	/**
	 * Directory for completed uploads
	 * @var string
	 */
	private $mainDirectory;

	/**
	 * Name of the temporary file. Used as a reference to make sure chunks get written to the right file.
	 * @var string
	 */
	private $tempName;

	/**
	 * Constructor function, sets the temporary directory and main directory
	 */
	public function __construct() {
		$this->setTempDirectory(self::TEMP_DIRECTORY);
		$this->setMainDirectory(self::MAIN_DIRECTORY);
	}

	/**
	 * Create a random file name for the file to use as it's being uploaded
	 * @param string $value Temporary filename
	 */
	public function setTempName($value = null) {
		if($value) {
			$this->tempName = $value;
		}
		else {
			$this->tempName = mt_rand() . '.tmp';
		}
	}

	/**
	 * Return the name of the temporary file
	 * @return string Temporary filename
	 */
	public function getTempName() {
		return $this->tempName;
	}

	/**
	 * Set the name of the temporary directory
	 * @param string $value Temporary directory
	 */
	public function setTempDirectory($value) {
		$this->tempDirectory = $value;
		return true;
	}

	/**
	 * Return the name of the temporary directory
	 * @return string Temporary directory
	 */
	public function getTempDirectory() {
		return $this->tempDirectory;
	}

	/**
	 * Set the name of the main directory
	 * @param string $value Main directory
	 */
	public function setMainDirectory($value) {
		$this->mainDirectory = $value;
	}

	/**
	 * Return the name of the main directory
	 * @return string Main directory
	 */
	public function getMainDirectory() {
		return $this->mainDirectory;
	}

	/**
	 * Function to upload the individual file chunks
	 * @return string JSON object with result of upload
	 */
	public function uploadFile() {

		//Make sure the total file we're writing to hasn't surpassed the file size limit
		if(file_exists($this->getTempDirectory() . $this->getTempName())) {
			if(filesize($this->getTempDirectory() . $this->getTempName()) > self::MAX_SIZE) {
				$this->abortUpload();
				return json_encode(array(
						'errorStatus' => 1,
						'errorText' => 'File is too large.'
					));
			}
		}

		//Open the raw POST data from php://input
		$fileData = file_get_contents('php://input');

		//Write the actual chunk to the larger file
		$handle = fopen($this->getTempDirectory() . $this->getTempName(), 'a');

		fwrite($handle, $fileData);
		fclose($handle);

		return json_encode(array(
			'key' => $this->getTempName(),
			'errorStatus' => 0
		));
	}

	/**
	 * Function for cancelling uploads while they're in-progress; deletes the temp file
	 * @return string JSON object with result of deletion
	 */
	public function abortUpload() {
		if(unlink($this->getTempDirectory() . $this->getTempName())) {
			return json_encode(array('errorStatus' => 0));
		}
		else {

			return json_encode(array(
				'errorStatus' => 1,
				'errorText' => 'Unable to delete temporary file.'
			));
		}
	}

	/**
	 * Function to rename and move the finished file
	 * @param  string $final_name Name to rename the finished upload to
	 * @return string JSON object with result of rename
	 */
	public function finishUpload($finalName) {
		if(rename($this->getTempDirectory() . $this->getTempName(), $this->getMainDirectory() . $finalName)) {
			return json_encode(array('errorStatus' => 0));
		}
		else {
			return json_encode(array(
				'errorStatus' => 1,
				'errorText' => 'Unable to move file after uploading.'
			));
		}
	}

	/**
	 * Basic php file upload function, used for unsupported browsers. 
	 * The output on success/failure is very basic, and it would be best to have these errors return the user to index.html
	 * with the errors printed on the form, but that is beyond the scope of this project as it is very application specific.
	 * @return string Success or failure of upload
	 */
	public function postUnsupported() {
		$name = $_FILES['bigUploadFile']['name'];
		$size = $_FILES['bigUploadFile']['size'];
		$tempName = $_FILES['bigUploadFile']['tmp_name'];

		if(filesize($tempName) > self::MAX_SIZE) {
			return 'File is too large.';
		}

		if(move_uploaded_file($tempName, $this->getMainDirectory() . $name)) {
			return 'File uploaded.';
		}
		else {
			return 'There was an error uploading the file';
		}

	}
}

//Instantiate the class
$bigUpload = new BigUpload;

//Set the temporary filename
$tempName = null;
if(isset($_GET['key'])) {
	$tempName = $_GET['key'];
}
if(isset($_POST['key'])) {
	$tempName = $_POST['key'];
}
$bigUpload->setTempName($tempName);

switch($_GET['action']) {
	case 'upload':
		print $bigUpload->uploadFile();
		break;
	case 'abort':
		print $bigUpload->abortUpload();
		break;
	case 'finish':
		print $bigUpload->finishUpload($_POST['name']);
		break;
	case 'post-unsupported':
		print $bigUpload->postUnsupported();
		break;
}
?>
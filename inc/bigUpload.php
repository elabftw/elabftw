<?php
session_start();
class BigUpload
{
	/**
	 * Temporary directory for uploading files
	 */
	const TEMP_DIRECTORY = '../uploads/tmp/';

	/**
	 * Directory files will be moved to after the upload is completed
	 */
	const MAIN_DIRECTORY = '../uploads/';

	/**
	 * Max allowed filesize. This is for unsupported browsers and
	 * as an additional security check in case someone bypasses the js filesize check.
	 *
	 * This must match the value specified in main.js
	 */
	const MAX_SIZE = 214748364800;

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
	public function finishUpload($realname, $type, $item_id) {
        // Create a clean filename : remplace all non letters/numbers by '.' (this way we don't lose the file extension)
        $realname = preg_replace('/[^A-Za-z0-9]/', '.', $realname);
        // get extension
        $path_info = pathinfo($realname);
        if (!empty($path_info['extension'])) {
            $ext = $path_info['extension'];
        } else {
            $ext = "unknown";
        }
        // Create a unique long filename + extension
        $longname = hash("sha512", uniqid(rand(), true)).".".$ext;
        // Try to move the file to its final place
		if(rename($this->getTempDirectory() . $this->getTempName(), $this->getMainDirectory() . $longname)) {
            // sql to put file in uploads table
            try
            {
                $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
                $ini_arr = parse_ini_file('../admin/config.ini');
                $bdd = new PDO('mysql:host='.$ini_arr['db_host'].';dbname='.$ini_arr['db_name'], $ini_arr['db_user'], $ini_arr['db_password'], $pdo_options);
            }
            catch(Exception $e)
            {
                die('Error : '.$e->getMessage());
            }
            $sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type) VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type)";
            $req = $bdd->prepare($sql);
            $result = $req->execute(array(
                'real_name' => $realname,
                'long_name' => $longname,
                // comment can be edited after upload
                'comment' => 'Click to add a comment',
                'item_id' => $item_id,
                'userid' => $_SESSION['userid'],
                'type' => $type
            ));
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
		print $bigUpload->finishUpload($_POST['realname'], $_POST['type'], $_POST['item_id']);
		break;
	case 'post-unsupported':
		print $bigUpload->postUnsupported();
		break;
}


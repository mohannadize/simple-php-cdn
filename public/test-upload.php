<?php
/**
 * This is a simple test script to diagnose upload issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>File Upload Test</h1>';

// Check upload permissions
$uploadDir = __DIR__ . '/uploads';
echo "<p>Upload directory: $uploadDir</p>";
echo "<p>Directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "</p>";
echo "<p>Directory is writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "</p>";

// Check PHP upload settings
echo "<h2>PHP Upload Settings</h2>";
echo "<ul>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>max_file_uploads: " . ini_get('max_file_uploads') . "</li>";
echo "<li>memory_limit: " . ini_get('memory_limit') . "</li>";
echo "</ul>";

// Check if we have a file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Upload Results</h2>";
    
    if (empty($_FILES)) {
        echo "<p>No files were submitted.</p>";
    } else {
        echo "<pre>" . print_r($_FILES, true) . "</pre>";
        
        // Check if there was an upload error
        if (isset($_FILES['test_image']['error'])) {
            $errorCode = $_FILES['test_image']['error'];
            
            $errorMessages = [
                UPLOAD_ERR_OK => 'No error, the file was uploaded successfully',
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            ];
            
            echo "<p>Error message: " . ($errorMessages[$errorCode] ?? 'Unknown error') . "</p>";
        }
        
        // Try to save the file
        if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
            $tempName = $_FILES['test_image']['tmp_name'];
            $fileName = $_FILES['test_image']['name'];
            $destPath = $uploadDir . '/' . $fileName;
            
            echo "<p>Attempting to move uploaded file from $tempName to $destPath</p>";
            
            if (move_uploaded_file($tempName, $destPath)) {
                echo "<p style='color: green;'>File uploaded successfully!</p>";
                echo "<p>File saved to: $destPath</p>";
                echo "<p><img src='/uploads/" . htmlspecialchars($fileName) . "' style='max-width: 300px;'></p>";
            } else {
                echo "<p style='color: red;'>Failed to move uploaded file!</p>";
                echo "<p>Last PHP error: " . error_get_last()['message'] . "</p>";
            }
        }
    }
}
?>

<h2>Test Upload Form</h2>
<form action="test-upload.php" method="post" enctype="multipart/form-data">
    <p>
        <label for="test_image">Select an image to upload:</label>
        <input type="file" name="test_image" id="test_image" accept="image/*">
    </p>
    <p>
        <button type="submit">Upload Test Image</button>
    </p>
</form>

<h2>Next Steps</h2>
<p>If the test upload works but the main CDN uploads don't, check the following:</p>
<ol>
    <li>Make sure Intervention Image is properly installed</li>
    <li>Check that the getFilePath() method is working in your PSR-7 UploadedFile object</li>
    <li>Verify that the JSON response is properly formatted</li>
    <li>Check the PHP error logs for detailed error messages</li>
</ol>

<p><a href="/">Return to main upload page</a></p> 
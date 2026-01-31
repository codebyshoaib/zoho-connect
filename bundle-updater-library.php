<?php
/**
 * Script to bundle Plugin Update Checker library
 * 
 * Run this script once to download and bundle the library:
 * php bundle-updater-library.php
 * 
 * This is an alternative to using Composer.
 */

// Configuration
$library_url = 'https://github.com/YahnisElsts/plugin-update-checker/archive/refs/heads/master.zip';
$target_dir = __DIR__ . '/includes/Infrastructure/Updater/plugin-update-checker';
$zip_file = __DIR__ . '/plugin-update-checker-temp.zip';

echo "Downloading Plugin Update Checker library...\n";

// Download the library
$zip_content = @file_get_contents($library_url);

if ($zip_content === false) {
    echo "Error: Could not download library. Please download manually from:\n";
    echo "https://github.com/YahnisElsts/plugin-update-checker\n";
    echo "Extract to: includes/Infrastructure/Updater/plugin-update-checker/\n";
    exit(1);
}

// Save to temp file
file_put_contents($zip_file, $zip_content);
echo "Downloaded successfully.\n";

// Extract
echo "Extracting...\n";
$zip = new ZipArchive();
if ($zip->open($zip_file) === TRUE) {
    // Create target directory
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Extract all files
    $zip->extractTo($target_dir);
    $zip->close();
    
    // Move files from subdirectory to target
    $extracted_dir = $target_dir . '/plugin-update-checker-master';
    if (is_dir($extracted_dir)) {
        // Move all files from extracted directory to target
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extracted_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($extracted_dir) + 1);
                $target_path = $target_dir . '/' . $relative_path;
                
                // Create directory if needed
                $target_file_dir = dirname($target_path);
                if (!is_dir($target_file_dir)) {
                    mkdir($target_file_dir, 0755, true);
                }
                
                copy($file_path, $target_path);
            }
        }
        
        // Remove extracted directory
        deleteDirectory($extracted_dir);
    }
    
    echo "Extracted successfully to: $target_dir\n";
    
    // Clean up
    unlink($zip_file);
    echo "Cleanup complete.\n";
    echo "\n✅ Library bundled successfully!\n";
    echo "You can now delete this script (bundle-updater-library.php)\n";
} else {
    echo "Error: Could not extract ZIP file.\n";
    unlink($zip_file);
    exit(1);
}

/**
 * Recursively delete a directory
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    return rmdir($dir);
}

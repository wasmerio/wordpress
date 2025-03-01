<?php
/**
 * Recursively scans a directory and builds a string with details for each file/dir.
 *
 * Each line has the format:
 *   <fullpath>,<ctime>,<mtime>,<atime>,<size>
 *
 * @param string $path The directory path to scan.
 *
 * @return string The resulting string.
 */
function scanDirectory($path) {
    $result = "";
    
    // Normalize the directory path to an absolute path
    $realPath = realpath($path);
    if ($realPath === false || !is_dir($realPath)) {
        return "Error: Provided path is not a directory.\n";
    }
    
    // Scan the directory contents
    $files = scandir($realPath);
    
    foreach ($files as $file) {
        // Skip the special entries '.' and '..'
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Construct the full path for the file/directory
        $fullPath = $realPath . DIRECTORY_SEPARATOR . $file;
        
        // Retrieve metadata for the item
        $ctime = filectime($fullPath);
        $mtime = filemtime($fullPath);
        $atime = fileatime($fullPath);
        $size  = filesize($fullPath);
        
        // Append the line with the full path and metadata
        $result .= "$fullPath,$ctime,$mtime,$atime,$size\n";
        
        // If the item is a directory, recursively scan it
        if (is_dir($fullPath)) {
            $result .= scanDirectory($fullPath);
        }
    }
    
    return $result;
}

echo scanDirectory('/');

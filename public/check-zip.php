<?php
echo '<h1>PHP Extension Check</h1>';
echo '<p>PHP Version: ' . phpversion() . '</p>';
echo '<p>Zip Extension: ' . (extension_loaded('zip') ? 'Loaded' : 'NOT Loaded') . '</p>';
echo '<p>ZipArchive Class: ' . (class_exists('ZipArchive') ? 'Available' : 'NOT Available') . '</p>';
echo '<h2>Loaded Extensions:</h2>';
echo '<pre>';
print_r(get_loaded_extensions());
echo '</pre>';
?>

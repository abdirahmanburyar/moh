<?php
if (class_exists('ZipArchive')) {
    echo "ZipArchive is available.\n";
} else {
    echo "ZipArchive is NOT available. You need to enable the zip extension.\n";
}
?>

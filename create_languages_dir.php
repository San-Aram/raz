<?php
// Create languages directory if it doesn't exist
$dir = __DIR__ . '/languages';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
echo "Languages directory created or already exists";
?>

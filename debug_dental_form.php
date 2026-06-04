<?php
// Debug the actual form processing for dental edit
require_once 'includes/auth.php';
require_once 'includes/database.php';

if ($_POST) {
    echo "Form POST data received:\n";
    echo "contains_fluoride isset: " . (isset($_POST['contains_fluoride']) ? 'true' : 'false') . "\n";
    echo "contains_fluoride value: " . var_export($_POST['contains_fluoride'] ?? 'NOT_SET', true) . "\n";
    
    $processed_value = isset($_POST['contains_fluoride']);
    echo "Processed value: " . var_export($processed_value, true) . "\n";
    
    $final_value = (bool)($processed_value ?? false) ? 1 : 0;
    echo "Final database value: " . var_export($final_value, true) . "\n";
    echo "Final value type: " . gettype($final_value) . "\n";
} else {
    echo "No POST data received.\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dental Debug Form</title>
</head>
<body>
    <h2>Debug Dental Fluoride Checkbox</h2>
    <form method="POST">
        <label>
            <input type="checkbox" name="contains_fluoride" value="1"> Contains Fluoride
        </label>
        <br><br>
        <button type="submit">Test Submit (Checked)</button>
    </form>
    
    <br>
    
    <form method="POST">
        <label>
            <input type="checkbox" name="contains_fluoride" value="1"> Contains Fluoride (Leave unchecked)
        </label>
        <br><br>
        <button type="submit">Test Submit (Unchecked)</button>
    </form>
</body>
</html>

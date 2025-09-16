<?php
require_once 'includes/config.php';



try {
    // Total clicks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clicks");

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

</body>
</html>
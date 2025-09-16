<?php
require_once 'includes/config.php';

}

// Get offer details
try {

    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();
    
    if (!$offer) {

        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($offer['name']) ?> - S2S Postback Checker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

        </div>
    </div>
</body>
</html>
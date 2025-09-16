<?php
require_once 'includes/config.php';

if (!$config['installed']) {
    header('Location: install/install.php');
    exit;
}

$pdo = getDbConnection();
if (!$pdo) {
    die('Database connection failed.');
}

$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            if ($action == 'add') {
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $goal_name = trim($_POST['goal_name'] ?? 'lead');
                $postback_template = trim($_POST['postback_template'] ?? '');
                
                if (empty($name)) {
                    $error = 'Offer name is required.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO offers (name, description, goal_name, postback_template) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $goal_name, $postback_template ?: null]);
                    $success = 'Offer created successfully.';
                }
                
            } elseif ($action == 'edit') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $goal_name = trim($_POST['goal_name'] ?? 'lead');
                $postback_template = trim($_POST['postback_template'] ?? '');
                $status = $_POST['status'] ?? 'active';
                
                if (empty($name) || $id <= 0) {
                    $error = 'Invalid offer data.';
                } else {
                    $stmt = $pdo->prepare("UPDATE offers SET name = ?, description = ?, goal_name = ?, postback_template = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $goal_name, $postback_template ?: null, $status, $id]);
                    $success = 'Offer updated successfully.';
                }
                
            } elseif ($action == 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM offers WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = 'Offer deleted successfully.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get offers
try {
    $stmt = $pdo->query("SELECT * FROM offers ORDER BY created_at DESC");
    $offers = $stmt->fetchAll();
} catch (PDOException $e) {
    $offers = [];
    $error = 'Failed to load offers: ' . $e->getMessage();
}

// Get offer for editing
$editOffer = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editOffer = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers - S2S Postback Checker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Offers Management</h1>
                <p class="text-secondary">Create and manage your offers with click tracking</p>
            </div>
            <button class="btn btn-primary" onclick="toggleForm()">
                <?= $editOffer ? 'Cancel Edit' : 'Add New Offer' ?>
            </button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <div class="card glass mb-3" id="offerForm" style="<?= !$editOffer && !isset($_POST['action']) ? 'display: none;' : '' ?>">
            <div class="card-header">
                <h3 class="card-title"><?= $editOffer ? 'Edit Offer' : 'Add New Offer' ?></h3>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="<?= $editOffer ? 'edit' : 'add' ?>">
                <?php if ($editOffer): ?>
                    <input type="hidden" name="id" value="<?= $editOffer['id'] ?>">
                <?php endif; ?>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Offer Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editOffer['name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Goal Name</label>
                        <input type="text" name="goal_name" class="form-control" value="<?= htmlspecialchars($editOffer['goal_name'] ?? 'lead') ?>" placeholder="lead">
                        <small class="text-secondary">Used in postback URL as {goal}</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the offer"><?= htmlspecialchars($editOffer['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Custom Postback Template (Optional)</label>
                    <textarea name="postback_template" class="form-control" rows="2" placeholder="Leave empty to use global template"><?= htmlspecialchars($editOffer['postback_template'] ?? '') ?></textarea>
                    <small class="text-secondary">Available tokens: {transaction_id}, {goal}, {name}, {email}, {offer_id}</small>
                </div>
                
                <?php if ($editOffer): ?>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($editOffer['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editOffer['status'] ?? 'active') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?= $editOffer ? 'Update Offer' : 'Create Offer' ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Offers List -->
        <div class="card glass">
            <div class="card-header">
                <h3 class="card-title">Your Offers</h3>
            </div>
            
            <?php if (empty($offers)): ?>
                <p class="text-secondary text-center p-3">No offers created yet. Click "Add New Offer" to get started.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Goal</th>
                                <th>Status</th>
                                <th>Click URL</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($offers as $offer): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($offer['name']) ?></strong>
                                    <?php if ($offer['description']): ?>
                                        <br><small class="text-secondary"><?= htmlspecialchars($offer['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($offer['goal_name']) ?></td>
                                <td>
                                    <span class="badge <?= $offer['status'] == 'active' ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= ucfirst($offer['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-center gap-1">
                                        <code class="code-block" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php 
                                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                                            $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
                                            if (substr($baseUrl, -1) !== '/') $baseUrl .= '/';
                                            ?>
                                            <?= htmlspecialchars($baseUrl) ?>click.php?offer=<?= $offer['id'] ?>&sub1={transaction_id}
                                        </code>
                                        <button class="btn btn-sm btn-secondary copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($baseUrl) ?>click.php?offer=<?= $offer['id'] ?>&sub1={transaction_id}')">
                                            📋
                                        </button>
                                    </div>
                                </td>
                                <td><?= date('M j, Y', strtotime($offer['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="?edit=<?= $offer['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <a href="offer.php?id=<?= $offer['id'] ?>" class="btn btn-sm btn-primary">View Page</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this offer?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $offer['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/app.js"></script>
    <script>
        function toggleForm() {
            const form = document.getElementById('offerForm');
            const button = document.querySelector('button[onclick="toggleForm()"]');
            
            if (form.style.display === 'none') {
                form.style.display = 'block';
                button.textContent = 'Cancel';
            } else {
                form.style.display = 'none';
                button.textContent = 'Add New Offer';
                // Clear form if not editing
                if (!button.textContent.includes('Cancel Edit')) {
                    form.querySelector('form').reset();
                }
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show feedback
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✓';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 1000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
            });
        }
    </script>
    
    <style>
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-success {
            background: rgba(0, 255, 136, 0.2);
            color: var(--success);
        }
        .badge-secondary {
            background: var(--glass-bg);
            color: var(--text-secondary);
        }
    </style>
</body>
</html>
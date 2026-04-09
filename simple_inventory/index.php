<?php
require 'db.php';

$error = '';

// Add product
if (isset($_POST['name']) && !empty($_POST['name'])) {
    try {
        $token = bin2hex(random_bytes(8));
        // Get next ID
        $result = $db->query("SELECT MAX(id) as max_id FROM products")->fetch();
        $nextId = ($result['max_id'] ?? 0) + 1;
        $db->prepare("INSERT INTO products (id, name, qty, token) VALUES (?, ?, ?, ?)")
           ->execute([$nextId, $_POST['name'], intval($_POST['qty']), $token]);
        // Refresh page after adding
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error adding product: " . $e->getMessage();
    }
}

// Delete product and renumber
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Get all products except the one being deleted
        $stmt = $db->prepare("SELECT id, name, qty, token FROM products WHERE id != ? ORDER BY id");
        $stmt->execute([$_GET['delete']]);
        $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete all products
        $db->exec("DELETE FROM products");
        
        // Re-insert with sequential IDs
        $stmt = $db->prepare("INSERT INTO products (id, name, qty, token) VALUES (?, ?, ?, ?)");
        $newId = 1;
        foreach ($allProducts as $p) {
            $stmt->execute([$newId, $p['name'], $p['qty'], $p['token']]);
            $newId++;
        }
        
        // Redirect to refresh
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

try {
    $products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Inventory Manager</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
    }
    .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    h2 { color: #333; margin-bottom: 25px; font-size: 28px; }
    .form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #667eea; }
    .form-group { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
    input { 
      padding: 10px 15px; 
      border: 2px solid #e0e0e0; 
      border-radius: 6px;
      font-size: 14px;
      flex: 1;
      min-width: 200px;
      transition: all 0.3s;
    }
    input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    button {
      padding: 10px 25px; 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.4); }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; text-align: left; font-weight: 600; }
    td { border-bottom: 1px solid #e0e0e0; padding: 12px 15px; }
    tr:hover { background: #f8f9fa; }
    .qr-cell { text-align: center; }
    img { max-width: 80px; height: auto; }
    .token-small { display: block; font-size: 12px; color: #999; margin-top: 5px; font-family: monospace; }
    .actions { display: flex; gap: 8px; }
    .btn-validate {
      padding: 6px 12px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
      text-decoration: none;
      display: inline-block;
      transition: all 0.2s;
    }
    .btn-validate:hover { background: #218838; }
    .btn-delete {
      padding: 6px 12px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-delete:hover { background: #c82333; }
    .label { font-weight: 600; color: #555; margin-bottom: 5px; display: block; }
  </style>
</head>
<body>
<div class="container">
<h2>📦 Inventory Manager</h2>

<?php if ($error): ?>
  <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<div class="form-section">
  <form method="POST">
    <div class="form-group">
      <div style="flex: 1; min-width: 200px;">
        <label class="label">Product Name</label>
        <input type="text" name="name" placeholder="Enter product name" required>
      </div>
      <div style="flex: 0 1 150px;">
        <label class="label">Quantity</label>
        <input type="number" name="qty" value="1" min="0" required>
      </div>
      <button type="submit">+ Add Product</button>
    </div>
  </form>
</div>

<table>
  <tr>
    <th>ID</th>
    <th>Product Name</th>
    <th>Quantity</th>
    <th>QR Code</th>
    <th>Actions</th>
  </tr>
  <?php if (empty($products)): ?>
  <tr>
    <td colspan="5" style="text-align: center; padding: 30px; color: #999;">No products yet. Add one to get started!</td>
  </tr>
  <?php else: ?>
  <?php foreach ($products as $p): ?>
  <tr>
    <td><strong>#<?= $p['id'] ?></strong></td>
    <td><?= htmlspecialchars($p['name']) ?></td>
    <td><strong><?= $p['qty'] ?></strong></td>
    <td class="qr-cell">
      <?php $url = "http://" . $_SERVER['HTTP_HOST'] . "/validate.php?token=" . $p['token']; ?>
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode($url) ?>" alt="QR Code">
      <span class="token-small"><?= substr($p['token'], 0, 8) ?>...</span>
    </td>
    <td>
      <div class="actions">
      <button class="btn-validate" onclick="showValidate('<?= addslashes($p['name']) ?>', '<?= $p['qty'] ?>', '<?= $p['token'] ?>'); return false;">✓ Validate</button>
        <button class="btn-delete" onclick="if(confirm('Delete <?= htmlspecialchars($p['name']) ?>?')) location.href='?delete=<?= $p['id'] ?>'">✕ Delete</button>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php endif; ?>
</table>

<div id="validateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 400px; width: 90%;">
    <h3 style="color: #28a745; margin-bottom: 15px;">✓ Validate Product</h3>
    <p><strong>Product:</strong> <span id="validateName"></span></p>
    <p><strong>Quantity:</strong> <span id="validateQty"></span></p>
    <p><strong>Token:</strong> <code id="validateToken" style="background: #f0f0f0; padding: 5px 8px; border-radius: 4px; display: block; margin-top: 10px; font-size: 12px; word-break: break-all;"></code></p>
    <button onclick="location.href='validate.php?token=' + document.getElementById('validateToken').textContent;" style="width: 100%; margin-top: 20px; padding: 12px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Go to Validation</button>
    <button onclick="document.getElementById('validateModal').style.display='none';" style="width: 100%; margin-top: 10px; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button>
  </div>
</div>

<script>
function showValidate(name, qty, token) {
  document.getElementById('validateName').textContent = name;
  document.getElementById('validateQty').textContent = qty;
  document.getElementById('validateToken').textContent = token;
  document.getElementById('validateModal').style.display = 'flex';
}
</script>

</div>
</body>
</html>

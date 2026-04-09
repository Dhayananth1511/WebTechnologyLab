<?php
require 'db.php';

$token = $_GET['token'] ?? '';

$stmt = $db->prepare("SELECT * FROM products WHERE token = ?");
$stmt->execute([$token]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Validate Product</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .container { max-width: 500px; width: 100%; }
    .box {
      padding: 40px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      background: white;
    }
    .box.valid { border-top: 5px solid #28a745; }
    .box.invalid { border-top: 5px solid #dc3545; }
    .box.warning { border-top: 5px solid #ffc107; }
    h2 { font-size: 32px; margin-bottom: 15px; }
    .valid h2 { color: #28a745; }
    .invalid h2 { color: #dc3545; }
    .warning h2 { color: #ffc107; }
    p { margin: 12px 0; font-size: 16px; color: #555; }
    strong { color: #333; }
    .highlight { background: #f0f0f0; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #667eea; }
    a { display: inline-block; margin-top: 25px; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; transition: all 0.2s; }
    a:hover { background: #764ba2; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.4); }
    .token-display { font-family: monospace; font-size: 14px; background: #f8f9fa; padding: 8px; border-radius: 4px; word-break: break-all; }
  </style>
</head>
<body>
<div class="container">

<?php if (!$token): ?>
  <div class="box warning">
    <h2>⚠️ No Token</h2>
    <p>No validation token was provided.</p>
    <p style="font-size: 14px; color: #999;">Scan a product QR code from the inventory to validate.</p>
    <a href="index.php">← Back to Inventory</a>
  </div>

<?php elseif ($product): ?>
  <div class="box valid">
    <h2>✅ Valid Product</h2>
    <div class="highlight">
      <p><strong>Product Name:</strong><br> <?= htmlspecialchars($product['name']) ?></p>
      <p style="margin-top: 10px;"><strong>Current Quantity:</strong><br> <span style="font-size: 24px; color: #28a745;">×<?= $product['qty'] ?></span></p>
    </div>
    <p style="font-size: 13px; color: #999;">
      <strong>Token:</strong><br>
      <span class="token-display"><?= $product['token'] ?></span>
    </p>
    <a href="index.php">← Back to Inventory</a>
  </div>

<?php else: ?>
  <div class="box invalid">
    <h2>❌ Invalid Product</h2>
    <p>This QR code is <strong>not recognized</strong> in the system.</p>
    <p style="font-size: 14px; color: #999; margin-top: 15px;">
      Token: <span class="token-display"><?= htmlspecialchars($token) ?></span>
    </p>
    <a href="index.php">← Back to Inventory</a>
  </div>
<?php endif; ?>

</div>
</body>
</html>

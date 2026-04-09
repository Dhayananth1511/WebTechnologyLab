<?php
$db = new PDO('sqlite:inventory.db');

// Create table if it doesn't exist
$db->exec("
  CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY,
    name TEXT,
    qty INTEGER,
    token TEXT UNIQUE
  )
");
?>

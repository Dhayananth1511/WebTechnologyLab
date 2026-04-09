# 📦 Simple Inventory Manager

A lightweight PHP-based inventory management system with QR code validation support.

## 📁 Project Files

### **db.php** - Database Configuration
- **Purpose**: Initializes SQLite database connection and creates the products table
- **What it does**:
  - Creates a PDO connection to `inventory.db`
  - Creates the `products` table with columns: `id`, `name`, `qty`, `token`
  - Runs automatically when included in other files

**Database Schema:**
```sql
CREATE TABLE products (
  id INTEGER PRIMARY KEY,
  name TEXT,
  qty INTEGER,
  token TEXT UNIQUE
)
```

---

### **index.php** - Main Dashboard
- **Purpose**: Main interface for managing inventory
- **Features**:
  - ✅ **Add Products**: Enter product name and quantity
  - ✅ **View Products**: Display all products in a table
  - ✅ **Generate QR Codes**: Each product gets a unique QR code
  - ✅ **Delete Products**: Remove products and renumber IDs
  - ✅ **Validate Products**: View product details via QR scan

**How it works:**
1. Form submits POST request with product name and quantity
2. Generates unique token using `random_bytes(8)`
3. Inserts product into database with auto-incremented ID
4. Displays all products with QR codes linking to validation page
5. Redirects to refresh after add/delete actions

**Key Elements:**
- Responsive UI with gradient background
- Real-time QR code generation using QR Server API
- Product table with Name, Quantity, QR Code, and Actions

---

### **validate.php** - Product Validation
- **Purpose**: Verify products via QR code or token lookup
- **How it works:**
  - Receives `token` parameter from URL or QR code
  - Searches database for matching product
  - Shows one of three states:

| State | Condition | Display |
|-------|-----------|---------|
| ✅ **Valid** | Token matches a product | Green - Shows product name & quantity |
| ❌ **Invalid** | Token doesn't match | Red - Product not found in system |
| ⚠️ **Warning** | No token provided | Yellow - No validation token given |

---

## 🚀 How to Use

### **Adding a Product**
1. Go to `index.php` (main page)
2. Enter **Product Name** (e.g., "Laptop", "USB Cable")
3. Enter **Quantity** (number of items)
4. Click **+ Add Product**
5. Product is saved and displayed in the table

### **Validating a Product**
1. **Option A**: Click **✓ Validate** button on any product
2. **Option B**: Scan the QR code with your phone
3. You'll see product details if valid, or error if invalid

### **Deleting a Product**
1. Click **✕ Delete** button next to a product
2. Confirm deletion
3. IDs renumber automatically

---

## 📊 Example Data Flow

```
User Input (index.php)
    ↓
Generate Token (random_bytes)
    ↓
Insert into SQLite (db.php)
    ↓
Display Table (index.php)
    ↓
Generate QR Code (QR Server API)
    ↓
User scans QR → validate.php?token=XXXXX
    ↓
Lookup token in database (validate.php)
    ↓
Display Result (✅ Valid / ❌ Invalid / ⚠️ Warning)
```

---

## 🛠️ Technical Details

### **Database File**
- Location: `inventory.db` (SQLite)
- Automatically created when `db.php` is first loaded
- Persists data between sessions

### **QR Code Generation**
- Uses external API: `https://api.qrserver.com`
- QR codes link to: `http://yourserver/validate.php?token=TOKEN`
- Size: 80×80 pixels

### **Token Format**
- Generated using: `bin2hex(random_bytes(8))`
- Format: 16 hexadecimal characters
- Example: `a1b2c3d4e5f6g7h8`
- Unique constraint ensures no duplicates

### **ID Management**
- Automatically incremented
- Renumbered sequentially after deletion
- Ensures consistent ordering

---

## 🔧 Setup Requirements

1. **PHP 7.0+** (PDO extension required)
2. **SQLite3** support in PHP
3. **Web Server** (Apache, Nginx, etc.)
4. **Folder Structure**:
   ```
   simple_inventory/
   ├── db.php
   ├── index.php
   ├── validate.php
   └── inventory.db (auto-created)
   ```

---

## 📝 Example Usage

### Add a Product
```
Name: iPhone 15
Quantity: 5
→ Token: 3f7a8b2c9e1d6f4a
→ QR Code Generated
```

### Validate Product
```
Scan QR Code
→ validate.php?token=3f7a8b2c9e1d6f4a
→ ✅ Valid Product
→ Shows: iPhone 15, Qty: 5
```

### Invalid Token
```
Visit: validate.php?token=fakeinvalidtoken
→ ❌ Invalid Product
→ "This QR code is not recognized in the system"
```

---

## ⚠️ Important Notes

- **Don't** modify `db.php` structure unless necessary (it handles database setup)
- **Tokens** are unique and permanent - don't delete a product if you need token history
- **QR codes** work offline only after generation (they link to your local server)
- **Database** persists across page refreshes - data is saved permanently

---

## 🎨 UI Features

- 📱 Fully responsive design
- 🎨 Gradient purple theme
- ✨ Smooth animations and hover effects
- 🔒 XSS protection via `htmlspecialchars()`
- 📋 Clean table layout with actions

---

*Created: April 2, 2026*

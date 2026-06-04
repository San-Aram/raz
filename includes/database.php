<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'fyp';
    private $username = 'root';
    private $password = '12345';
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        return $this->conn;
    }
}

class Medication {
    private $conn;
    private $table = 'medications';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '', $pregnancy_filter = '', $lactation_filter = '') {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (active_ingredient LIKE :search OR class LIKE :search2)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($pregnancy_filter === 'yes') {
            $query .= " AND pregnancy_safe = 1";
        } elseif ($pregnancy_filter === 'no') {
            $query .= " AND pregnancy_safe = 0";
        }

        if ($lactation_filter === 'yes') {
            $query .= " AND lactation_safe = 1";
        } elseif ($lactation_filter === 'no') {
            $query .= " AND lactation_safe = 0";
        }

        $query .= " ORDER BY active_ingredient ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByActiveIngredientName($active_ingredient) {
        $query = "SELECT * FROM " . $this->table . " WHERE active_ingredient = :active_ingredient";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':active_ingredient', $active_ingredient);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByActiveIngredient($active_ingredient) {
        $query = "SELECT * FROM " . $this->table . " WHERE TRIM(LOWER(active_ingredient)) = TRIM(LOWER(:active_ingredient))";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':active_ingredient', $active_ingredient);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                 (active_ingredient, class, mechanism_of_action, indication, side_effects, 
                  contraindication, pregnancy_safe, lactation_safe, 
                  adult_dosage_1, adult_frequency_1, adult_dosage_2, adult_frequency_2,
                  children_dosage_1, children_frequency_1, children_dosage_2, children_frequency_2)
                 VALUES 
                 (:active_ingredient, :class, :mechanism_of_action, :indication, :side_effects,
                  :contraindication, :pregnancy_safe, :lactation_safe,
                  :adult_dosage_1, :adult_frequency_1, :adult_dosage_2, :adult_frequency_2,
                  :children_dosage_1, :children_frequency_1, :children_dosage_2, :children_frequency_2)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':active_ingredient' => $data['active_ingredient'],
            ':class' => $data['class'],
            ':mechanism_of_action' => $data['mechanism_of_action'],
            ':indication' => $data['indication'],
            ':side_effects' => $data['side_effects'],
            ':contraindication' => $data['contraindication'],
            ':pregnancy_safe' => $data['pregnancy_safe'] ? 1 : 0,
            ':lactation_safe' => $data['lactation_safe'] ? 1 : 0,
            ':adult_dosage_1' => $data['adult_dosage_1'] ?? '',
            ':adult_frequency_1' => $data['adult_frequency_1'] ?? '',
            ':adult_dosage_2' => $data['adult_dosage_2'] ?? '',
            ':adult_frequency_2' => $data['adult_frequency_2'] ?? '',
            ':children_dosage_1' => $data['children_dosage_1'] ?? '',
            ':children_frequency_1' => $data['children_frequency_1'] ?? '',
            ':children_dosage_2' => $data['children_dosage_2'] ?? '',
            ':children_frequency_2' => $data['children_frequency_2'] ?? ''
        ]);
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                 active_ingredient = :active_ingredient, class = :class, mechanism_of_action = :mechanism_of_action,
                 indication = :indication, side_effects = :side_effects,
                 contraindication = :contraindication, pregnancy_safe = :pregnancy_safe,
                 lactation_safe = :lactation_safe,
                 adult_dosage_1 = :adult_dosage_1, adult_frequency_1 = :adult_frequency_1,
                 adult_dosage_2 = :adult_dosage_2, adult_frequency_2 = :adult_frequency_2,
                 children_dosage_1 = :children_dosage_1, children_frequency_1 = :children_frequency_1,
                 children_dosage_2 = :children_dosage_2, children_frequency_2 = :children_frequency_2
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':active_ingredient' => $data['active_ingredient'],
            ':class' => $data['class'],
            ':mechanism_of_action' => $data['mechanism_of_action'],
            ':indication' => $data['indication'],
            ':side_effects' => $data['side_effects'],
            ':contraindication' => $data['contraindication'],
            ':pregnancy_safe' => $data['pregnancy_safe'] ? 1 : 0,
            ':lactation_safe' => $data['lactation_safe'] ? 1 : 0,
            ':adult_dosage_1' => $data['adult_dosage_1'] ?? '',
            ':adult_frequency_1' => $data['adult_frequency_1'] ?? '',
            ':adult_dosage_2' => $data['adult_dosage_2'] ?? '',
            ':adult_frequency_2' => $data['adult_frequency_2'] ?? '',
            ':children_dosage_1' => $data['children_dosage_1'] ?? '',
            ':children_frequency_1' => $data['children_frequency_1'] ?? '',
            ':children_dosage_2' => $data['children_dosage_2'] ?? '',
            ':children_frequency_2' => $data['children_frequency_2'] ?? ''
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}

class Product {
    private $conn;
    private $table = 'products';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '') {
        $query = "SELECT p.*, m.active_ingredient as medication_name FROM " . $this->table . " p 
                 LEFT JOIN medications m ON p.medication_id = m.id 
                 WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (p.product_name LIKE :search 
                       OR p.company LIKE :search 
                       OR p.active_ingredient LIKE :search 
                       OR p.barcode LIKE :search
                       OR p.dose LIKE :search
                       OR p.form LIKE :search
                       OR m.active_ingredient LIKE :search2)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        $query .= " ORDER BY p.product_name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT p.*, m.active_ingredient as medication_name FROM " . $this->table . " p 
                 LEFT JOIN medications m ON p.medication_id = m.id 
                 WHERE p.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByBarcode($barcode) {
        $query = "SELECT p.*, m.active_ingredient as medication_name FROM " . $this->table . " p 
                 LEFT JOIN medications m ON p.medication_id = m.id 
                 WHERE p.barcode = :barcode";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':barcode', $barcode, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        try {
            // Find medication by active ingredient
            $medicationQuery = "SELECT id FROM medications WHERE TRIM(LOWER(active_ingredient)) = TRIM(LOWER(:active_ingredient))";
            $stmt = $this->conn->prepare($medicationQuery);
            $stmt->execute([':active_ingredient' => $data['active_ingredient']]);
            $medication = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $medication_id = $medication ? $medication['id'] : null;

            $query = "INSERT INTO " . $this->table . " 
                     (barcode, product_name, company, active_ingredient, dose, form, price, image_url, medication_id, expiry_date, quantity, low_stock_threshold)
                     VALUES 
                     (:barcode, :product_name, :company, :active_ingredient, :dose, :form, :price, :image_url, :medication_id, :expiry_date, :quantity, :low_stock_threshold)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':barcode' => $data['barcode'],
                ':product_name' => $data['product_name'],
                ':company' => $data['company'],
                ':active_ingredient' => $data['active_ingredient'],
                ':dose' => $data['dose'],
                ':form' => $data['form'],
                ':price' => $data['price'],
                ':image_url' => $data['image_url'],
                ':medication_id' => $medication_id,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':quantity' => $data['quantity'] ?? 0,
                ':low_stock_threshold' => $data['low_stock_threshold'] ?? 10
            ]);
        } catch (PDOException $e) {
            // Handle duplicate key constraint violation
            if ($e->getCode() == 23000) {
                throw new Exception("Product with this barcode already exists");
            }
            throw $e;
        }
    }

    public function update($id, $data) {
        // Find medication by active ingredient
        $medicationQuery = "SELECT id FROM medications WHERE active_ingredient = :active_ingredient";
        $stmt = $this->conn->prepare($medicationQuery);
        $stmt->execute([':active_ingredient' => $data['active_ingredient']]);
        $medication = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $medication_id = $medication ? $medication['id'] : null;

        $query = "UPDATE " . $this->table . " SET 
                 barcode = :barcode, product_name = :product_name, company = :company,
                 active_ingredient = :active_ingredient, dose = :dose, form = :form,
                 price = :price, image_url = :image_url, medication_id = :medication_id,
                 expiry_date = :expiry_date, quantity = :quantity, low_stock_threshold = :low_stock_threshold
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':barcode' => $data['barcode'],
            ':product_name' => $data['product_name'],
            ':company' => $data['company'],
            ':active_ingredient' => $data['active_ingredient'],
            ':dose' => $data['dose'],
            ':form' => $data['form'],
            ':price' => $data['price'],
            ':image_url' => $data['image_url'],
            ':medication_id' => $medication_id,
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':quantity' => $data['quantity'] ?? 0,
            ':low_stock_threshold' => $data['low_stock_threshold'] ?? 10
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Inventory management methods
    public function getLowStockItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE quantity <= low_stock_threshold AND quantity > 0 ORDER BY quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOutOfStockItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE quantity = 0 ORDER BY product_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiringItems($days = 30) {
        $query = "SELECT * FROM " . $this->table . " WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY) ORDER BY expiry_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiredItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() ORDER BY expiry_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStock($id, $quantity) {
        $query = "UPDATE " . $this->table . " SET quantity = :quantity WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id, ':quantity' => $quantity]);
    }
}

class Cosmetic {
    private $conn;
    private $table = 'cosmetics';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '') {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (name LIKE :search 
                       OR company LIKE :search 
                       OR barcode LIKE :search
                       OR notes LIKE :search
                       OR class LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $query .= " ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByBarcode($barcode) {
        $query = "SELECT * FROM " . $this->table . " WHERE barcode = :barcode";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':barcode', $barcode, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (barcode, name, company, notes, class, price, image_url, expiry_date, quantity, low_stock_threshold)
                     VALUES 
                     (:barcode, :name, :company, :notes, :class, :price, :image_url, :expiry_date, :quantity, :low_stock_threshold)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':barcode' => $data['barcode'],
                ':name' => $data['name'],
                ':company' => $data['company'],
                ':notes' => $data['notes'] ?? '',
                ':class' => $data['class'],
                ':price' => $data['price'] ?? 0,
                ':image_url' => $data['image_url'] ?? '',
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':quantity' => $data['quantity'] ?? 0,
                ':low_stock_threshold' => $data['low_stock_threshold'] ?? 10
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Cosmetic product with this barcode already exists");
            }
            throw $e;
        }
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                 barcode = :barcode, name = :name, company = :company,
                 notes = :notes, class = :class,
                 price = :price, image_url = :image_url,
                 expiry_date = :expiry_date, quantity = :quantity, low_stock_threshold = :low_stock_threshold
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':barcode' => $data['barcode'],
            ':name' => $data['name'],
            ':company' => $data['company'],
            ':notes' => $data['notes'] ?? '',
            ':class' => $data['class'],
            ':price' => $data['price'],
            ':image_url' => $data['image_url'],
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':quantity' => $data['quantity'] ?? 0,
            ':low_stock_threshold' => $data['low_stock_threshold'] ?? 10
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Inventory management methods
    public function getLowStockItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE quantity <= low_stock_threshold AND quantity > 0 ORDER BY quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOutOfStockItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE quantity = 0 ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiringItems($days = 30) {
        $query = "SELECT * FROM " . $this->table . " WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY) ORDER BY expiry_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiredItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() ORDER BY expiry_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStock($id, $quantity) {
        $query = "UPDATE " . $this->table . " SET quantity = :quantity WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id, ':quantity' => $quantity]);
    }
}

class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function authenticate($username, $password) {
        $query = "SELECT id, username, password, role, full_name FROM " . $this->table . " WHERE username = ? AND is_active = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username]);
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $user['password'])) {
                // Update last login
                $updateQuery = "UPDATE " . $this->table . " SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->execute([$user['id']]);
                
                return $user;
            }
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT id, username, role, full_name, email, created_at, last_login FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class Sale {
    private $conn;
    private $table = 'sales';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (sale_number, seller_id, customer_name, customer_phone, subtotal, tax_amount, discount_amount, total_amount, payment_method, payment_status, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $success = $stmt->execute([
            $data['sale_number'],
            $data['seller_id'],
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null,
            $data['subtotal'],
            $data['tax_amount'] ?? 0,
            $data['discount_amount'] ?? 0,
            $data['total_amount'],
            $data['payment_method'] ?? 'cash',
            $data['payment_status'] ?? 'paid',
            $data['notes'] ?? null
        ]);
        
        if ($success) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT s.*, u.username as seller_name FROM " . $this->table . " s 
                  LEFT JOIN users u ON s.seller_id = u.id 
                  WHERE s.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBySaleNumber($saleNumber) {
        $query = "SELECT s.*, u.username as seller_name FROM " . $this->table . " s 
                  LEFT JOIN users u ON s.seller_id = u.id 
                  WHERE s.sale_number = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$saleNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByDateRange($startDate, $endDate, $sellerId = null) {
        $query = "SELECT s.*, u.username as seller_name FROM " . $this->table . " s 
                  LEFT JOIN users u ON s.seller_id = u.id 
                  WHERE DATE(s.sale_date) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        
        if ($sellerId) {
            $query .= " AND s.seller_id = ?";
            $params[] = $sellerId;
        }
        
        $query .= " ORDER BY s.sale_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateSaleNumber() {
        $prefix = "RZ" . date('Ymd');
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE sale_number LIKE ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNumber = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $nextNumber;
    }

    public function getTodaysSales($sellerId = null) {
        $query = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM " . $this->table . " 
                  WHERE DATE(sale_date) = CURDATE()";
        $params = [];
        
        if ($sellerId) {
            $query .= " AND seller_id = ?";
            $params[] = $sellerId;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class SaleItem {
    private $conn;
    private $table = 'sale_items';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (sale_id, product_type, product_id, product_name, product_barcode, unit_price, quantity, line_total) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['sale_id'],
            $data['product_type'],
            $data['product_id'],
            $data['product_name'],
            $data['product_barcode'] ?? null,
            $data['unit_price'],
            $data['quantity'],
            $data['line_total']
        ]);
    }

    public function getBySaleId($saleId) {
        $query = "SELECT * FROM " . $this->table . " WHERE sale_id = ? ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$saleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Dental {
    private $conn;
    private $table = 'dental';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '') {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (name LIKE :search 
                       OR company LIKE :search 
                       OR barcode LIKE :search
                       OR notes LIKE :search
                       OR class LIKE :search
                       OR subcategory LIKE :search
                       OR custom_class LIKE :search
                       OR custom_size LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $query .= " ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByBarcode($barcode) {
        $query = "SELECT * FROM " . $this->table . " WHERE barcode = :barcode";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':barcode', $barcode, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (barcode, name, company, notes, class, subcategory, custom_size, custom_class, price, age_group, contains_fluoride, expiry_date, quantity, low_stock_threshold)
                     VALUES 
                     (:barcode, :name, :company, :notes, :class, :subcategory, :custom_size, :custom_class, :price, :age_group, :contains_fluoride, :expiry_date, :quantity, :low_stock_threshold)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':barcode' => $data['barcode'],
                ':name' => $data['name'],
                ':company' => $data['company'],
                ':notes' => $data['notes'] ?? '',
                ':class' => $data['class'],
                ':subcategory' => $data['subcategory'] ?? null,
                ':custom_size' => $data['custom_size'] ?? null,
                ':custom_class' => $data['custom_class'] ?? null,
                ':price' => $data['price'],
                ':age_group' => $data['age_group'] ?? 'both',
                ':contains_fluoride' => (bool)($data['contains_fluoride'] ?? false) ? 1 : 0,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':quantity' => $data['quantity'] ?? 0,
                ':low_stock_threshold' => $data['low_stock_threshold'] ?? 10
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Dental product with this barcode already exists");
            }
            throw $e;
        }
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                 barcode = :barcode, name = :name, company = :company,
                 notes = :notes, class = :class,
                 subcategory = :subcategory, custom_size = :custom_size, custom_class = :custom_class,
                 price = :price, age_group = :age_group, contains_fluoride = :contains_fluoride,
                 expiry_date = :expiry_date, quantity = :quantity, low_stock_threshold = :low_stock_threshold
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':barcode' => $data['barcode'],
            ':name' => $data['name'],
            ':company' => $data['company'],
            ':notes' => $data['notes'] ?? '',
            ':class' => $data['class'],
            ':subcategory' => $data['subcategory'] ?? null,
            ':custom_size' => $data['custom_size'] ?? null,
            ':custom_class' => $data['custom_class'] ?? null,
            ':price' => $data['price'],
            ':age_group' => $data['age_group'] ?? 'both',
            ':contains_fluoride' => (bool)($data['contains_fluoride'] ?? false) ? 1 : 0,
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':quantity' => $data['quantity'] ?? 0,
            ':low_stock_threshold' => $data['low_stock_threshold'] ?? 10
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Inventory management methods
    public function getLowStockItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE quantity <= low_stock_threshold AND quantity > 0 ORDER BY quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOutOfStockItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE quantity = 0 ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiringItems($days = 30) {
        $query = "SELECT * FROM " . $this->table . " WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY) ORDER BY expiry_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiredItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() ORDER BY expiry_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStock($id, $quantity) {
        $query = "UPDATE " . $this->table . " SET quantity = :quantity WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id, ':quantity' => $quantity]);
    }
}

// Initialize database connection
$database = new Database();
$db = $database->connect();
?>

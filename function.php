<?php
include_once 'conn.php';

class Functions {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // User-related methods
    /**
     * Check if an email already exists in the users table
     * @param string $email The email to check
     * @return bool True if email exists, false otherwise
     */
    public function checkEmailExists($email) {
        try {
            $query = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("checkEmailExists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Authenticate a user by email and password
     * @param string $email User's email
     * @param string $password User's password
     * @return array|bool User data if authenticated, false otherwise
     */
    public function login($email, $password) {
        try {
            $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a new user and generate an OTP for 2FA
     * @param string $full_name User's full name
     * @param string $email User's email
     * @param string $password User's password
     * @param string $role User's role (default: 'staff')
     * @return int|bool User ID if successful, false otherwise
     */
    public function register($full_name, $email, $password, $role = 'staff') {
        try {
            // Validate inputs
            if (empty($full_name) || empty($email) || empty($password)) {
                return false;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            if (strlen($password) < 6) {
                return false;
            }
            if (!in_array($role, ['admin', 'staff'])) {
                $role = 'staff'; // Default to staff if role is invalid
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            if ($stmt->execute()) {
                $user_id = $this->db->lastInsertId();
                // Generate and store OTP
                $this->generateAndStoreOTP($user_id);
                return $user_id;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Register error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a 6-digit OTP and store it with a 5-minute expiration
     * @param int $user_id The user ID
     * @return string|bool The generated OTP if successful, false otherwise
     */
    public function generateAndStoreOTP($user_id) {
        try {
            $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $query = "UPDATE users SET otp = :otp, otp_expires_at = :expires_at WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':otp', $otp);
            $stmt->bindParam(':expires_at', $expires_at);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            return $otp;
        } catch (PDOException $e) {
            error_log("generateAndStoreOTP error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify the OTP for a user
     * @param int $user_id The user ID
     * @param string $otp The OTP to verify
     * @return bool True if OTP is valid, false otherwise
     */
    public function verifyOTP($user_id, $otp) {
        try {
            $query = "SELECT otp, otp_expires_at FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user || !$user['otp']) {
                return false;
            }

            $current_time = new DateTime();
            $expires_at = new DateTime($user['otp_expires_at']);

            if ($current_time > $expires_at) {
                $this->nullifyOTP($user_id);
                return false;
            }

            if ($user['otp'] === $otp) {
                $this->nullifyOTP($user_id);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("verifyOTP error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Nullify the OTP and expiration time for a user
     * @param int $user_id The user ID
     */
    public function nullifyOTP($user_id) {
        try {
            $query = "UPDATE users SET otp = NULL, otp_expires_at = NULL WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("nullifyOTP error: " . $e->getMessage());
        }
    }

    /**
     * Get user information by ID
     * @param int $user_id The user ID
     * @return array|bool User data if found, false otherwise
     */
    public function GetUserInfo($user_id) {
        try {
            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("GetUserInfo error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users with the role 'staff'
     * @return array Array of staff users
     */
    public function getStaffUsers() {
        try {
            $query = "SELECT * FROM users WHERE role = 'staff'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getStaffUsers error: " . $e->getMessage());
            return [];
        }
    }

    // Item-related methods
    /**
     * Get all items with calculated total cost
     * @return array Array of items
     */
    public function getAllItems() {
        try {
            $query = "SELECT *, (quantity * cost) as total_cost FROM items";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllItems error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single item by ID
     * @param int $item_id The item ID
     * @return array|bool Item data if found, false otherwise
     */
    public function getItem($item_id) {
        try {
            $query = "SELECT * FROM items WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $item_id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getItem error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a new item to the inventory
     * @param string $name Item name
     * @param string $unit Unit of measurement
     * @param int $quantity Quantity in stock
     * @param int $min_stock_level Minimum stock level
     * @param float $cost Cost per unit
     * @param float $total_cost Total cost (quantity * cost)
     * @param string $location Storage location
     * @param string $supplier Supplier name
     * @param string $description Item description
     * @return bool True if successful, false otherwise
     */
    public function addItem($name, $unit, $quantity, $min_stock_level, $cost, $total_cost, $location, $supplier, $description) {
        try {
            // Validate inputs
            if (empty($name) || empty($unit) || $quantity < 0 || $min_stock_level < 0 || $cost < 0 || $total_cost < 0) {
                return false;
            }

            $query = "INSERT INTO items (name, unit, quantity, min_stock_level, cost, total_cost, location, supplier, description) 
                      VALUES (:name, :unit, :quantity, :min_stock_level, :cost, :total_cost, :location, :supplier, :description)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':min_stock_level', $min_stock_level);
            $stmt->bindParam(':cost', $cost);
            $stmt->bindParam(':total_cost', $total_cost);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':supplier', $supplier);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("addItem error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing item
     * @param int $id Item ID
     * @param string $name Item name
     * @param string $unit Unit of measurement
     * @param int $quantity Quantity in stock
     * @param int $min_stock_level Minimum stock level
     * @param float $cost Cost per unit
     * @param float $total_cost Total cost (quantity * cost)
     * @param string $location Storage location
     * @param string $supplier Supplier name
     * @param string $description Item description
     * @return bool True if successful, false otherwise
     */
    public function updateItem($id, $name, $unit, $quantity, $min_stock_level, $cost, $total_cost, $location, $supplier, $description) {
        try {
            // Validate inputs
            if (empty($name) || empty($unit) || $quantity < 0 || $min_stock_level < 0 || $cost < 0 || $total_cost < 0) {
                return false;
            }

            $query = "UPDATE items SET name = :name, unit = :unit, quantity = :quantity, min_stock_level = :min_stock_level, 
                      cost = :cost, total_cost = :total_cost, location = :location, supplier = :supplier, description = :description 
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':min_stock_level', $min_stock_level);
            $stmt->bindParam(':cost', $cost);
            $stmt->bindParam(':total_cost', $total_cost);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':supplier', $supplier);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("updateItem error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an item by ID
     * @param int $id Item ID
     * @return bool True if successful, false otherwise
     */
    public function deleteItem($id) {
        try {
            $query = "DELETE FROM items WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("deleteItem error: " . $e->getMessage());
            return false;
        }
    }

    // Order-related methods
    /**
     * Get orders assigned to a specific supplier
     * @param int $supplier_id The supplier's user ID
     * @return array Array of orders
     */
    public function getOrdersBySupplier($supplier_id) {
        try {
            $query = "SELECT * FROM purchase_orders WHERE supplier_id = :supplier_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':supplier_id', $supplier_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getOrdersBySupplier error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single order by PO number
     * @param string $po_number The purchase order number
     * @return array|bool Order data if found, false otherwise
     */
    public function getOrder($po_number) {
        try {
            $query = "SELECT * FROM purchase_orders WHERE po_number = :po_number";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':po_number', $po_number);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getOrder error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all orders with supplier names
     * @return array Array of orders
     */
    public function getAllOrders() {
        try {
            $query = "SELECT po.*, u.full_name as supplier_name 
                      FROM purchase_orders po 
                      LEFT JOIN users u ON po.supplier_id = u.id";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllOrders error: " . $e->getMessage());
            return [];
        }
    }

    /**
 * Add a new purchase order
 * @param string $item_name Item name
 * @param int $quantity Quantity ordered
 * @param float $unit_cost Cost per unit
 * @param float $total_cost Total cost (quantity * unit_cost)
 * @param int $supplier_id Supplier's user ID
 * @return string|bool The po_number if successful, false otherwise
 */
public function addOrder($item_name, $quantity, $unit_cost, $total_cost, $supplier_id) {
    try {
        // Validate inputs
        if (empty($item_name)) {
            error_log("addOrder failed: item_name is empty");
            return false;
        }
        if ($quantity <= 0) {
            error_log("addOrder failed: quantity <= 0 ($quantity)");
            return false;
        }
        if ($unit_cost <= 0) {
            error_log("addOrder failed: unit_cost <= 0 ($unit_cost)");
            return false;
        }
        if ($total_cost <= 0) {
            error_log("addOrder failed: total_cost <= 0 ($total_cost)");
            return false;
        }
        if ($supplier_id <= 0) {
            error_log("addOrder failed: supplier_id <= 0 ($supplier_id)");
            return false;
        }

        // Generate a random po_number (e.g., PO-ABC123)
        $maxAttempts = 10;
        $attempt = 0;
        do {
            $randomString = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $po_number = "PO-" . $randomString;

            $query = "SELECT COUNT(*) FROM purchase_orders WHERE po_number = :po_number";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':po_number', $po_number);
            $stmt->execute();
            $exists = $stmt->fetchColumn() > 0;

            $attempt++;
            if ($attempt >= $maxAttempts) {
                error_log("addOrder failed: Could not generate a unique po_number after $maxAttempts attempts");
                return false;
            }
        } while ($exists);

        $order_date = date('Y-m-d H:i:s');
        $status = 'ordered';

        $query = "INSERT INTO purchase_orders (po_number, item_name, quantity, unit_cost, total_cost, supplier_id, order_date, status) 
                  VALUES (:po_number, :item_name, :quantity, :unit_cost, :total_cost, :supplier_id, :order_date, :status)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':po_number', $po_number);
        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unit_cost', $unit_cost);
        $stmt->bindParam(':total_cost', $total_cost);
        $stmt->bindParam(':supplier_id', $supplier_id);
        $stmt->bindParam(':order_date', $order_date);
        $stmt->bindParam(':status', $status);
        if (!$stmt->execute()) {
            error_log("addOrder failed: Database insertion failed for po_number=$po_number");
            return false;
        }
        error_log("addOrder success: po_number=$po_number");
        return $po_number; // Return the po_number on success
    } catch (PDOException $e) {
        error_log("addOrder error: " . $e->getMessage());
        return false;
    }
}

    /**
     * Update an existing purchase order
     * @param string $po_number Purchase order number
     * @param string $item_name Item name
     * @param int $quantity Quantity ordered
     * @param float $unit_cost Cost per unit
     * @param float $total_cost Total cost (quantity * unit_cost)
     * @param int $supplier_id Supplier's user ID
     * @return bool True if successful, false otherwise
     */
    public function updateOrder($po_number, $item_name, $quantity, $unit_cost, $total_cost, $supplier_id) {
        try {
            // Validate inputs
            if (empty($item_name) || $quantity <= 0 || $unit_cost <= 0 || $total_cost <= 0 || $supplier_id <= 0) {
                return false;
            }

            $query = "UPDATE purchase_orders SET item_name = :item_name, quantity = :quantity, unit_cost = :unit_cost, 
                      total_cost = :total_cost, supplier_id = :supplier_id 
                      WHERE po_number = :po_number";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':po_number', $po_number);
            $stmt->bindParam(':item_name', $item_name);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':unit_cost', $unit_cost);
            $stmt->bindParam(':total_cost', $total_cost);
            $stmt->bindParam(':supplier_id', $supplier_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("updateOrder error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the status of a purchase order
     * @param string $po_number Purchase order number
     * @param string $status New status
     * @return bool True if successful, false otherwise
     */
    public function updateOrderStatus($po_number, $status) {
        try {
            // Validate status
            $valid_statuses = ['ordered', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($status, $valid_statuses)) {
                return false;
            }

            $query = "UPDATE purchase_orders SET status = :status WHERE po_number = :po_number";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':po_number', $po_number);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("updateOrderStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a purchase order
     * @param string $po_number Purchase order number
     * @return bool True if successful, false otherwise
     */
    public function cancelOrder($po_number) {
        try {
            $query = "UPDATE purchase_orders SET status = 'cancelled' WHERE po_number = :po_number";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':po_number', $po_number);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("cancelOrder error: " . $e->getMessage());
            return false;
        }
    }

    // Report-related methods
    /**
     * Get items with low stock (quantity <= min_stock_level)
     * @return array Array of low stock items
     */
    public function getLowStockItems() {
        try {
            $query = "SELECT * FROM items WHERE quantity <= min_stock_level";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getLowStockItems error: " . $e->getMessage());
            return [];
        }
    }
    /**
 * Get unique item names from purchase_orders with their details (only delivered orders)
 * @return array Array of items with item_name, unit_cost, supplier_id, and total_ordered_quantity
 */
public function getUniqueOrderedItems() {
    try {
        $query = "SELECT item_name, unit_cost, supplier_id, SUM(quantity) as total_ordered_quantity 
                  FROM purchase_orders 
                  WHERE status = 'delivered' 
                  GROUP BY item_name, unit_cost, supplier_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("getUniqueOrderedItems: " . json_encode($items)); // Debug log
        return $items;
    } catch (PDOException $e) {
        error_log("getUniqueOrderedItems error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total quantity already added to items table for a given item_name
 * @param string $item_name Item name
 * @return int Total quantity in items table
 */
/**
 * Get total quantity of an item in the items table
 */
/**
 * Get total quantity of an item in the items table
 */
public function getTotalQuantityInItems($item_name) {
    try {
        $sql = "SELECT SUM(quantity) as total 
                FROM items 
                WHERE name = :item_name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':item_name', $item_name, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("getTotalQuantityInItems: PDO Exception - " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total approved request quantity by item name
 */
public function getTotalApprovedRequestQuantityByName($item_name) {
    try {
        $sql = "SELECT SUM(quantity) as total 
                FROM item_requests 
                WHERE item_name = :item_name AND status = 'approved'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':item_name', $item_name, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("getTotalApprovedRequestQuantityByName: PDO Exception - " . $e->getMessage());
        return 0;
    }
}
/**
 * Get item details by ID
 * @param int $id Item ID
 * @return array|bool Item details or false if not found
 */
public function getItemById($id) {
    try {
        $query = "SELECT * FROM items WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($item) {
            error_log("getItemById for ID $id: " . json_encode($item)); // Debug log
            return $item;
        }
        error_log("getItemById for ID $id: Item not found");
        return false;
    } catch (PDOException $e) {
        error_log("getItemById error: " . $e->getMessage());
        return false;
    }
}   
/**
 * Save a guest user with the role 'user'
 * @param string $name The name of the guest user
 * @return int|bool The guest user ID if successful, false otherwise
 */
public function saveGuestUser($name) {
    // Trim the name
    $name = trim($name);

    try {
        // Check if the name already exists (case-insensitive)
        $sql = "SELECT id FROM guest_users WHERE LOWER(name) = LOWER(:name) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Name already exists, return the existing guest_user_id
            return $row['id'];
        } else {
            // Name doesn't exist, create a new entry
            $sql = "INSERT INTO guest_users (name, created_at) VALUES (:name, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $result = $stmt->execute();
            if ($result) {
                return $this->db->lastInsertId(); // Get the new ID
            }
            return false;
        }
    } catch (PDOException $e) {
        // Log the error (in a production environment)
        error_log("Error in saveGuestUser: " . $e->getMessage());
        return false;
    }
}
        /**
 * Add a new item request by a guest user
 * @param int $guest_user_id The guest user ID
 * @param string $item_name The name of the item
 * @param int $quantity The requested quantity
 * @return bool True if successful, false otherwise
 */
public function addItemRequest(int $guest_user_id, int $item_id, string $item_name, int $quantity): bool {
    try {
        $sql = "INSERT INTO item_requests (guest_user_id, item_id, item_name, quantity, request_date, status) 
                VALUES (:guest_user_id, :item_id, :item_name, :quantity, NOW(), 'pending')";
        error_log("addItemRequest: Executing query: $sql with guest_user_id=$guest_user_id, item_id=$item_id, item_name=$item_name, quantity=$quantity");
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("addItemRequest: Failed to prepare statement: " . implode(", ", $this->db->errorInfo()));
            return false;
        }

        $stmt->bindParam(':guest_user_id', $guest_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
        $stmt->bindParam(':item_name', $item_name, PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

        $result = $stmt->execute();
        if (!$result) {
            error_log("addItemRequest: Failed to execute statement: " . implode(", ", $stmt->errorInfo()));
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Error in addItemRequest: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all item requests for a guest user
 * @param int $guest_user_id The guest user ID
 * @return array Array of item requests
 */
public function getItemRequestsByUser(int $guest_user_id): array {
    try {
        $sql = "SELECT * FROM item_requests WHERE guest_user_id = :guest_user_id ORDER BY request_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':guest_user_id', $guest_user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getItemRequestsByUser: " . $e->getMessage());
        return [];
    }
}public function getItemRequest(int $request_id) {
    try {
        $sql = "SELECT * FROM item_requests WHERE id = :request_id";
        error_log("getItemRequest: Executing query: $sql with request_id=$request_id");
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("getItemRequest: Fetched request for request_id=$request_id: " . json_encode($result));
        return $result ?: null;
    } catch (PDOException $e) {
        error_log("Error in getItemRequest: " . $e->getMessage());
        return null;
    }
}
/**
 * Update an item request
 * @param int $request_id The request ID
 * @param string $item_name The updated item name
 * @param int $quantity The updated quantity
 * @return bool True if successful, false otherwise
 */
public function updateItemRequest(int $request_id, string $item_name, int $quantity): bool {
    try {
        if ($quantity <= 0) {
            error_log("updateItemRequest: Invalid quantity ($quantity) for request_id=$request_id");
            return false;
        }

        // Check if the request exists and is pending
        $sql = "SELECT * FROM item_requests WHERE id = :request_id AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            error_log("updateItemRequest: Request not found or not pending for request_id=$request_id");
            return false;
        }

        // Verify the item exists and quantity is valid
        $item = $this->getItem($request['item_id']);
        if (!$item) {
            error_log("updateItemRequest: Item not found for item_id={$request['item_id']}");
            return false;
        }
        if ($quantity > $item['quantity']) {
            error_log("updateItemRequest: Quantity ($quantity) exceeds available ({$item['quantity']}) for item_id={$request['item_id']}");
            return false;
        }

        // Only update the quantity, leave item_name unchanged
        $sql = "UPDATE item_requests SET quantity = :quantity WHERE id = :request_id AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $result = $stmt->execute();

        if (!$result) {
            error_log("updateItemRequest: SQL execution failed: " . implode(", ", $stmt->errorInfo()));
            return false;
        }

        $rowCount = $stmt->rowCount();
        error_log("updateItemRequest: Updated $rowCount rows for request_id=$request_id with quantity=$quantity");
        return $rowCount > 0;
    } catch (PDOException $e) {
        error_log("updateItemRequest: PDO Exception - " . $e->getMessage());
        return false;
    }
}
/**
 * Delete an item request
 * @param int $request_id The request ID
 * @return bool True if successful, false otherwise
 */
public function deleteItemRequest($request_id) {
    try {
        $query = "DELETE FROM item_requests WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $request_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("deleteItemRequest error: " . $e->getMessage());
        return false;
    }
}
public function cancelItemRequest(int $request_id): bool {
    try {
        $sql = "UPDATE item_requests SET status = 'canceled' WHERE id = :request_id AND status = 'pending'";
        error_log("cancelItemRequest: Executing query: $sql with request_id=$request_id");
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (!$result) {
            error_log("cancelItemRequest: Failed to execute statement: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
        $rowCount = $stmt->rowCount();
        error_log("cancelItemRequest: Updated $rowCount rows for request_id=$request_id");
        return $rowCount > 0;
    } catch (PDOException $e) {
        error_log("Error in cancelItemRequest: " . $e->getMessage());
        return false;
    }
}
/**
 * Approve an item request and deduct the quantity from the item stock
 * @param int $request_id The request ID
 * @return bool True if successful, false otherwise
 */
/**
 * Approve an item request and deduct the quantity from the item stock
 * @param int $request_id The request ID
 * @return bool True if successful, false otherwise
 */
public function approveRequest(int $request_id): bool {
    try {
        // Fetch the request
        $sql = "SELECT * FROM item_requests WHERE id = :request_id AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            error_log("approveRequest: Request not found or not pending for request_id=$request_id");
            return false;
        }

        // Check item availability
        $item = $this->getItem($request['item_id']);
        if (!$item || $request['quantity'] > $item['quantity']) {
            error_log("approveRequest: Insufficient stock for item_id={$request['item_id']} (requested: {$request['quantity']}, available: {$item['quantity']})");
            return false;
        }

        // Update request status to 'approved'
        $sql = "UPDATE item_requests SET status = 'approved' WHERE id = :request_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            error_log("approveRequest: Failed to update request status: " . implode(", ", $stmt->errorInfo()));
            return false;
        }

        // Deduct quantity from items table
        $new_quantity = $item['quantity'] - $request['quantity'];
        $sql = "UPDATE items SET quantity = :new_quantity WHERE id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':new_quantity', $new_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':item_id', $request['item_id'], PDO::PARAM_INT);
        if (!$stmt->execute()) {
            error_log("approveRequest: Failed to update item quantity: " . implode(", ", $stmt->errorInfo()));
            return false;
        }

        error_log("approveRequest: Successfully approved request_id=$request_id, deducted {$request['quantity']} from item_id={$request['item_id']}");
        return true;
    } catch (PDOException $e) {
        error_log("approveRequest: PDO Exception - " . $e->getMessage());
        return false;
    }
}

/**
 * Reject an item request
 * @param int $request_id The request ID
 * @return bool True if successful, false otherwise
 */
public function rejectRequest(int $request_id): bool {
    try {
        $sql = "UPDATE item_requests SET status = 'rejected' WHERE id = :request_id AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $result = $stmt->execute();

        if (!$result) {
            error_log("rejectRequest: Failed to execute: " . implode(", ", $stmt->errorInfo()));
            return false;
        }

        $rowCount = $stmt->rowCount();
        error_log("rejectRequest: Updated $rowCount rows for request_id=$request_id to 'rejected'");
        return $rowCount > 0;
    } catch (PDOException $e) {
        error_log("rejectRequest: PDO Exception - " . $e->getMessage());
        return false;
    }
}

/**
 * Get all item requests (for admin view)
 * @return array Array of all item requests
 */
public function getAllItemRequests(): array {
    try {
        $sql = "SELECT ir.*, gu.name as guest_user_name 
                FROM item_requests ir 
                LEFT JOIN guest_users gu ON ir.guest_user_id = gu.id 
                ORDER BY ir.request_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getAllItemRequests: PDO Exception - " . $e->getMessage());
        return [];
    }
}
/**
 * Get total quantity of approved requests for an item by item_id
 * @param int $item_id The item ID
 * @return int Total quantity from approved requests
 */
public function getTotalApprovedRequestQuantity(int $item_id): int {
    try {
        $sql = "SELECT SUM(quantity) as total 
                FROM item_requests 
                WHERE item_id = :item_id AND status = 'approved'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("getTotalApprovedRequestQuantity: PDO Exception - " . $e->getMessage());
        return 0;
    }
}
/**
 * Get recent item requests
 * @param int $limit Number of requests to fetch
 * @return array Array of recent requests
 */
public function getRecentItemRequests(int $limit = 10): array {
    try {
        $sql = "SELECT * FROM item_requests ORDER BY request_date DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getRecentItemRequests: PDO Exception - " . $e->getMessage());
        return [];
    }
}
/**
 * Get total quantity of approved requests for an item by name
 * @param string $itemName The item name
 * @return int Total quantity from approved requests
 */
public function addDelivery($po_number, $item_name, $delivered_quantity) {
    try {
        $sql = "INSERT INTO delivery_tracking (po_number, item_name, delivered_quantity) 
                VALUES (:po_number, :item_name, :delivered_quantity)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':po_number', $po_number, PDO::PARAM_STR);
        $stmt->bindParam(':item_name', $item_name, PDO::PARAM_STR);
        $stmt->bindParam(':delivered_quantity', $delivered_quantity, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("addDelivery: PDO Exception - " . $e->getMessage());
        return false;
    }
}

/**
 * Get total delivered quantity for an item
 */
public function getTotalDeliveredQuantity($item_name) {
    try {
        $sql = "SELECT SUM(delivered_quantity) as total 
                FROM delivery_tracking 
                WHERE item_name = :item_name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':item_name', $item_name, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("getTotalDeliveredQuantity: PDO Exception - " . $e->getMessage());
        return 0;
    }
}
/**
 * Fetch all pending item requests
 */
/**
 * Get pending item requests with guest names and item names
 */
public function getPendingItemRequests() {
    try {
        $sql = "SELECT ir.*, gu.name AS guest_name, i.name AS item_name 
                FROM item_requests ir 
                LEFT JOIN guest_users gu ON ir.guest_user_id = gu.id 
                LEFT JOIN items i ON ir.item_id = i.id 
                WHERE ir.status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getPendingItemRequests: PDO Exception - " . $e->getMessage());
        return [];
    }
}

/**
 * Get approved item requests with guest names and item names, limited to $limit
 */
public function getApprovedItemRequests($limit) {
    try {
        $sql = "SELECT ir.*, gu.name AS guest_name, i.name AS item_name 
                FROM item_requests ir 
                LEFT JOIN guest_users gu ON ir.guest_user_id = gu.id 
                LEFT JOIN items i ON ir.item_id = i.id 
                WHERE ir.status = 'approved' 
                ORDER BY ir.request_date DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getApprovedItemRequests: PDO Exception - " . $e->getMessage());
        return [];
    }
}
}
?>
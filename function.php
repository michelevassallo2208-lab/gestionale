<?php
if (!function_exists('connectDB')) {
    function connectDB() {
        $host = 'localhost';
        $db = 'ccsud_gestionale';
        $user = 'root';
        $pass = '';
        try {
            return new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}

function addUser($username, $password, $role_id) {
    $db = connectDB();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $hash, $role_id]);
}

function getUserByUsername($username) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function verifyUser($username, $password) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

function getProductsBySubcategory($subcategoryId) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT id, name, description, price, quantity FROM products WHERE subcategory_id = ?");
    $stmt->execute([$subcategoryId]);
    return $stmt->fetchAll();
}

function logActivity($userId, $action, $description, $ipAddress, $userAgent)
{
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            INSERT INTO logs (user_id, action, description, ip_address, user_agent, created_at) 
            VALUES (:user_id, :action, :description, :ip_address, :user_agent, NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
        ]);
    } catch (PDOException $e) {
        error_log("Errore nel logActivity: " . $e->getMessage());
        // Puoi decidere come gestire ulteriormente l'errore
    }
}

function executeAndLog(PDO $db, $query, $params, $userId, $action)
{
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        // Registra l'attività nei log
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        logActivity($userId, $action, $query, $ipAddress, $userAgent);

        return $stmt;
    } catch (PDOException $e) {
        die('Errore durante l\'esecuzione della query: ' . $e->getMessage());
    }
}

/**
 * Nuove Funzioni Aggiunte
 */

// Recupera tutte le categorie
function getCategories(PDO $db) {
    $stmt = $db->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Recupera tutti i fornitori
function getSuppliers(PDO $db) {
    $stmt = $db->prepare("SELECT id, name FROM suppliers ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>

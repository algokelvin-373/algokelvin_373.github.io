<?php
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = $data["username"] ?? '';
$nameFirst = $data['name_first'] ?? '';
$nameLast = $data['name_last'] ?? '';
$phoneNumber = $data['phone_number'] ?? '';
$password = password_hash($data['password'], PASSWORD_DEFAULT);

$secretKey = "$password";
$token = hash_hmac('sha256', $username . $password, $secretKey);

function checkUserInDB($pdo, $username, $phoneNumber) {
    $query = "SELECT * FROM sy_user WHERE username = :username AND phone_number = :phone_number LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':phone_number', $phoneNumber);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertToTable_sy_user($pdo) {
    global $nameFirst, $nameLast, $username, $password, $token, $phoneNumber;

    // Query Insert New User
    $query = "INSERT INTO sy_user(
        `name_first`, 
        `name_last`, 
        `username`, 
        `password`, 
        `token`, 
        `device`, 
        `phone_number`
    ) VALUES (
        :name_first,
        :name_last,
        :username,
        :password,
        :token,
        '',
        :phone_number
    )";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':name_first', $nameFirst);
    $stmt->bindParam(':name_last', $nameLast);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':phone_number', $phoneNumber);
    $stmt->execute();
}

function register($pdo) {
    global $username, $phoneNumber;

    insertToTable_sy_user($pdo);
    if (checkUserInDB($pdo, $username, $phoneNumber)) {
        return true;
    } else {
        return false;
    }
}

function sendRequestRegister() {
    global $username, $phoneNumber;

    $pdo = connectDB(); // connect DB

    // Check in DB to avoid duplicate data regsiter by username and phone_number
    if (checkUserInDB($pdo, $username, $phoneNumber)) {
        resultResponse(0, 'Error', 'Username has existed');
    } else {
        if (register($pdo)) {
            resultResponse(1,'Success', 'Success to register');
        } else {
            resultResponse(0,'Failed', 'Failed to register');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sendRequestRegister();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Method not allowed"]);
}

?>
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ceylon";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if customers table exists
$table_check = $conn->query("SHOW TABLES LIKE 'customers'");
if ($table_check->num_rows == 0) {
    // Create customers table if it doesn't exist
    $create_table = "CREATE TABLE customers (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        nic VARCHAR(20),
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        dob DATE,
        phone_personal VARCHAR(20),
        phone_work VARCHAR(20),
        address TEXT,
        postal_code VARCHAR(20),
        country VARCHAR(100) DEFAULT 'Sri Lanka',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_table)) {
        die("Error creating table: " . $conn->error);
    }
}

// Check and update customers table structure if needed
$check_columns = $conn->query("SHOW COLUMNS FROM customers");
if ($check_columns) {
    $columns = [];
    while($row = $check_columns->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    // Add missing columns if they don't exist
    $required_columns = [
        'username' => "ALTER TABLE customers ADD COLUMN username VARCHAR(100) NOT NULL AFTER id",
        'nic' => "ALTER TABLE customers ADD COLUMN nic VARCHAR(20) AFTER username",
        'email' => "ALTER TABLE customers ADD COLUMN email VARCHAR(100) NOT NULL UNIQUE AFTER nic",
        'password' => "ALTER TABLE customers ADD COLUMN password VARCHAR(255) NOT NULL AFTER email",
        'dob' => "ALTER TABLE customers ADD COLUMN dob DATE AFTER password",
        'phone_personal' => "ALTER TABLE customers ADD COLUMN phone_personal VARCHAR(20) AFTER dob",
        'phone_work' => "ALTER TABLE customers ADD COLUMN phone_work VARCHAR(20) AFTER phone_personal",
        'address' => "ALTER TABLE customers ADD COLUMN address TEXT AFTER phone_work",
        'postal_code' => "ALTER TABLE customers ADD COLUMN postal_code VARCHAR(20) AFTER address",
        'country' => "ALTER TABLE customers ADD COLUMN country VARCHAR(100) DEFAULT 'Sri Lanka' AFTER postal_code",
        'created_at' => "ALTER TABLE customers ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER country",
        'updated_at' => "ALTER TABLE customers ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];

    foreach ($required_columns as $column => $sql) {
        if (!in_array($column, $columns)) {
            if (!$conn->query($sql)) {
                die("Error adding column $column: " . $conn->error);
            }
        }
    }
} else {
    die("Error checking table structure: " . $conn->error);
}
?>
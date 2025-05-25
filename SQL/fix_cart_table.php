<?php
// Include the database connection file
include('connection.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cart Table Fix</h1>";

// Drop existing foreign key constraints if they exist
$drop_constraints = "ALTER TABLE cart 
                    DROP FOREIGN KEY IF EXISTS fk_cart_product1,
                    DROP FOREIGN KEY IF EXISTS fk_cart_cus_payment1";
$conn->query($drop_constraints);

// Modify the table structure
$alter_table = "ALTER TABLE cart 
                MODIFY cart_id INT(11) NOT NULL AUTO_INCREMENT,
                MODIFY userid INT(11) NOT NULL,
                MODIFY qty INT(11) DEFAULT NULL,
                MODIFY total_cost DOUBLE DEFAULT NULL,
                MODIFY product_product_id INT(11) NOT NULL,
                ADD COLUMN IF NOT EXISTS cus_payment_payment_id INT(11) NULL,
                ADD PRIMARY KEY (cart_id),
                ADD KEY fk_cart_product1_idx (product_product_id),
                ADD KEY fk_cart_cus_payment1_idx (cus_payment_payment_id),
                ADD CONSTRAINT fk_cart_product1 FOREIGN KEY (product_product_id) REFERENCES product (product_id),
                ADD CONSTRAINT fk_cart_cus_payment1 FOREIGN KEY (cus_payment_payment_id) REFERENCES cus_payment (payment_id)";

if ($conn->query($alter_table) === TRUE) {
    echo "<p>Successfully updated cart table structure.</p>";
} else {
    echo "<p>Error modifying table: " . $conn->error . "</p>";
}

// Check the structure again to confirm
echo "<h2>Updated Cart Table Structure</h2>";
$cart_structure = $conn->query("DESCRIBE cart");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $cart_structure->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>
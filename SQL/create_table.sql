CREATE TABLE IF NOT EXISTS cus_payment (
    payment_id INT NOT NULL AUTO_INCREMENT,
    status INT DEFAULT NULL,
    amount DOUBLE DEFAULT NULL,
    payment_date DATETIME DEFAULT NULL,
    invoice_invoice_id INT NOT NULL,
    PRIMARY KEY (payment_id)
);
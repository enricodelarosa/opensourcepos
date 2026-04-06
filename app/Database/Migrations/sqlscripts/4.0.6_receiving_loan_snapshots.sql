--
-- Store per-party loan balance snapshots around a receiving.
-- Cash split continues to come from ospos_receiving_payments.
--

CREATE TABLE IF NOT EXISTS `ospos_receiving_loan_snapshots` (
    `id`                    int(10) NOT NULL AUTO_INCREMENT,
    `receiving_id`          int(10) NOT NULL,
    `supplier_id`           int(10) NOT NULL,
    `customer_id`           int(10) NOT NULL,
    `luna_id`               int(10) DEFAULT NULL,
    `loan_balance_before`   decimal(15,2) NOT NULL DEFAULT 0.00,
    `loan_deduction_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `loan_balance_after`    decimal(15,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    UNIQUE KEY `receiving_supplier` (`receiving_id`, `supplier_id`),
    KEY `supplier_id` (`supplier_id`),
    KEY `customer_id` (`customer_id`),
    KEY `luna_id` (`luna_id`),
    CONSTRAINT `ospos_receiving_loan_snapshots_ibfk_receiving`
        FOREIGN KEY (`receiving_id`) REFERENCES `ospos_receivings` (`receiving_id`) ON DELETE CASCADE,
    CONSTRAINT `ospos_receiving_loan_snapshots_ibfk_supplier`
        FOREIGN KEY (`supplier_id`) REFERENCES `ospos_suppliers` (`person_id`),
    CONSTRAINT `ospos_receiving_loan_snapshots_ibfk_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `ospos_customers` (`person_id`),
    CONSTRAINT `ospos_receiving_loan_snapshots_ibfk_luna`
        FOREIGN KEY (`luna_id`) REFERENCES `ospos_lunas` (`luna_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

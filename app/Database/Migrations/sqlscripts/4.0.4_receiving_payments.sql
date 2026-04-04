--
-- Store per-supplier cash payment amounts for each receiving.
-- Supports split cash payments between primary supplier and partner (landowner/tenant).
--

CREATE TABLE IF NOT EXISTS `ospos_receiving_payments` (
    `id`           int(10) NOT NULL AUTO_INCREMENT,
    `receiving_id` int(10) NOT NULL,
    `supplier_id`  int(10) NOT NULL,
    `cash_amount`  decimal(15,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `receiving_id` (`receiving_id`),
    KEY `supplier_id` (`supplier_id`),
    CONSTRAINT `ospos_receiving_payments_ibfk_receiving`
        FOREIGN KEY (`receiving_id`) REFERENCES `ospos_receivings` (`receiving_id`) ON DELETE CASCADE,
    CONSTRAINT `ospos_receiving_payments_ibfk_supplier`
        FOREIGN KEY (`supplier_id`) REFERENCES `ospos_suppliers` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

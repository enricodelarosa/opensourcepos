--
-- Add customer_id to suppliers table to link suppliers to customers
--

ALTER TABLE `ospos_suppliers` ADD COLUMN `customer_id` int(10) DEFAULT NULL AFTER `category`;
ALTER TABLE `ospos_suppliers` ADD KEY `customer_id` (`customer_id`);
ALTER TABLE `ospos_suppliers` ADD CONSTRAINT `ospos_suppliers_ibfk_customer` FOREIGN KEY (`customer_id`) REFERENCES `ospos_customers` (`person_id`);

--
-- Create customer_loans table to track loan/credit transactions
--

CREATE TABLE IF NOT EXISTS `ospos_customer_loans` (
    `loan_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(10) NOT NULL,
    `sale_id` int(10) DEFAULT NULL,
    `receiving_id` int(10) DEFAULT NULL,
    `loan_amount` decimal(15,2) NOT NULL COMMENT 'Positive = new loan/debt, Negative = payment/deduction',
    `transaction_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `comment` text,
    PRIMARY KEY (`loan_id`),
    KEY `customer_id` (`customer_id`),
    KEY `sale_id` (`sale_id`),
    KEY `receiving_id` (`receiving_id`),
    CONSTRAINT `ospos_customer_loans_ibfk_customer` FOREIGN KEY (`customer_id`) REFERENCES `ospos_customers` (`person_id`),
    CONSTRAINT `ospos_customer_loans_ibfk_sale` FOREIGN KEY (`sale_id`) REFERENCES `ospos_sales` (`sale_id`),
    CONSTRAINT `ospos_customer_loans_ibfk_receiving` FOREIGN KEY (`receiving_id`) REFERENCES `ospos_receivings` (`receiving_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

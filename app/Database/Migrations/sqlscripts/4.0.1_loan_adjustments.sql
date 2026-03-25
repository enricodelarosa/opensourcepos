--
-- Create loan_adjustments table to track manual loan balance changes with cash impact.
--
-- loan_amount: positive = increase loan (business gives cash OUT to supplier)
--              negative = decrease loan (business receives cash IN from supplier)
--
-- Each adjustment also creates a corresponding ospos_customer_loans entry (tracked via loan_id).
-- On soft-delete the linked customer_loans row is hard-deleted to reverse the balance.
--

CREATE TABLE IF NOT EXISTS `ospos_loan_adjustments` (
    `adjustment_id` int(11) NOT NULL AUTO_INCREMENT,
    `adjustment_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `supplier_id` int(11) NOT NULL,
    `customer_id` int(11) NOT NULL,
    `loan_amount` decimal(15,2) NOT NULL COMMENT 'Positive = increase loan (cash out), Negative = decrease loan (cash in)',
    `comment` text,
    `employee_id` int(11) NOT NULL,
    `loan_id` int(11) DEFAULT NULL COMMENT 'FK to the ospos_customer_loans row created by this adjustment',
    `deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`adjustment_id`),
    KEY `supplier_id` (`supplier_id`),
    KEY `customer_id` (`customer_id`),
    KEY `employee_id` (`employee_id`),
    KEY `loan_id` (`loan_id`),
    CONSTRAINT `ospos_loan_adjustments_ibfk_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `ospos_people` (`person_id`),
    CONSTRAINT `ospos_loan_adjustments_ibfk_customer` FOREIGN KEY (`customer_id`) REFERENCES `ospos_customers` (`person_id`),
    CONSTRAINT `ospos_loan_adjustments_ibfk_employee` FOREIGN KEY (`employee_id`) REFERENCES `ospos_employees` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Register the loan_adjustments module, permission, and grant to admin (person_id = 1)
--

INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
    ('module_loan_adjustments', 'module_loan_adjustments_desc', 65, 'loan_adjustments');

INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES
    ('loan_adjustments', 'loan_adjustments');

INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`) VALUES
    ('loan_adjustments', 1);

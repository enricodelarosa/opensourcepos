--
-- Create cash_movements table for non-sale cash put into the drawer.
--

CREATE TABLE IF NOT EXISTS `ospos_cash_movements` (
    `movement_id` int(11) NOT NULL AUTO_INCREMENT,
    `movement_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `amount` decimal(15,2) NOT NULL,
    `description` text,
    `employee_id` int(11) NOT NULL,
    `deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`movement_id`),
    KEY `movement_time` (`movement_time`),
    KEY `employee_id` (`employee_id`),
    CONSTRAINT `ospos_cash_movements_ibfk_employee` FOREIGN KEY (`employee_id`) REFERENCES `ospos_employees` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

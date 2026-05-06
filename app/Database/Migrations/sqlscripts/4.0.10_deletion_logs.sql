--
-- Keep structured audit records for business-object deletions.
--

CREATE TABLE IF NOT EXISTS `ospos_deletion_logs` (
    `log_id` int(11) NOT NULL AUTO_INCREMENT,
    `entity_type` varchar(64) NOT NULL,
    `entity_id` int(11) NOT NULL,
    `entity_label` varchar(255) NOT NULL DEFAULT '',
    `deleted_by` int(10) DEFAULT NULL,
    `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_data` text NOT NULL,
    PRIMARY KEY (`log_id`),
    KEY `entity` (`entity_type`, `entity_id`),
    KEY `deleted_by` (`deleted_by`),
    KEY `deleted_at` (`deleted_at`),
    CONSTRAINT `ospos_deletion_logs_ibfk_employee`
        FOREIGN KEY (`deleted_by`) REFERENCES `ospos_employees` (`person_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

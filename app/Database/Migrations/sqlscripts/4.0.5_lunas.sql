--
-- Replace one-to-one supplier partnerships with land-parcel (luna) management.
--

ALTER TABLE `ospos_suppliers`
    DROP FOREIGN KEY `ospos_suppliers_ibfk_partner`;

ALTER TABLE `ospos_suppliers`
    DROP KEY `partner_supplier_id`;

ALTER TABLE `ospos_suppliers`
    DROP COLUMN `partner_supplier_id`;

CREATE TABLE IF NOT EXISTS `ospos_lunas` (
    `luna_id` int(10) NOT NULL AUTO_INCREMENT,
    `area_name` varchar(255) NOT NULL,
    `barangay` varchar(255) NOT NULL DEFAULT '',
    `landowner_id` int(10) NOT NULL,
    `tenant_id` int(10) DEFAULT NULL,
    `deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`luna_id`),
    KEY `landowner_id` (`landowner_id`),
    KEY `tenant_id` (`tenant_id`),
    CONSTRAINT `ospos_lunas_ibfk_landowner`
        FOREIGN KEY (`landowner_id`) REFERENCES `ospos_suppliers` (`person_id`) ON DELETE CASCADE,
    CONSTRAINT `ospos_lunas_ibfk_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `ospos_suppliers` (`person_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ospos_customer_loans`
    ADD COLUMN `luna_id` int(10) DEFAULT NULL AFTER `receiving_id`,
    ADD KEY `luna_id` (`luna_id`),
    ADD CONSTRAINT `ospos_customer_loans_ibfk_luna`
        FOREIGN KEY (`luna_id`) REFERENCES `ospos_lunas` (`luna_id`) ON DELETE SET NULL;

ALTER TABLE `ospos_receivings`
    ADD COLUMN `luna_id` int(10) DEFAULT NULL AFTER `supplier_id`,
    ADD KEY `luna_id` (`luna_id`),
    ADD CONSTRAINT `ospos_receivings_ibfk_luna`
        FOREIGN KEY (`luna_id`) REFERENCES `ospos_lunas` (`luna_id`) ON DELETE SET NULL;

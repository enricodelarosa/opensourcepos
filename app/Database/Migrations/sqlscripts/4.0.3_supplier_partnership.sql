--
-- Add partner_supplier_id to ospos_suppliers to link landowner-tenant pairs.
-- The relationship is considered symmetric: A links to B means B is A's partner.
--

ALTER TABLE `ospos_suppliers`
    ADD COLUMN `partner_supplier_id` int(10) DEFAULT NULL AFTER `customer_id`,
    ADD KEY `partner_supplier_id` (`partner_supplier_id`),
    ADD CONSTRAINT `ospos_suppliers_ibfk_partner`
        FOREIGN KEY (`partner_supplier_id`) REFERENCES `ospos_suppliers` (`person_id`)
        ON DELETE SET NULL;

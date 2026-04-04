--
-- Register the cash_summary module, permission, and grant to admin (person_id = 1)
--

INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
    ('module_cash_summary', 'module_cash_summary_desc', 66, 'cash_summary');

INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES
    ('cash_summary', 'cash_summary');

INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`) VALUES
    ('cash_summary', 1);

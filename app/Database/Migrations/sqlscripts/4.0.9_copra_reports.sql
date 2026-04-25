--
-- Register the copra_reports module, permission, and grant to admin (person_id = 1)
--

INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
    ('module_copra_reports', 'module_copra_reports_desc', 67, 'copra_reports');

INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES
    ('copra_reports', 'copra_reports');

INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES
    ('copra_reports', 1, 'home');

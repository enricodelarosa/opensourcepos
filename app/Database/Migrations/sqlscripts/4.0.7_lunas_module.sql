--
-- Register the lunas module, permission, and grant to admin (person_id = 1)
--

INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
    ('module_lunas', 'module_lunas_desc', 45, 'lunas');

INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES
    ('lunas', 'lunas');

INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`) VALUES
    ('lunas', 1);

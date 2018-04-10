-- ELABFTW
/* the groups */
INSERT INTO `groups` (`group_id`, `group_name`, `is_sysadmin`, `is_admin`, `can_lock`) VALUES
(1, 'Sysadmins', 1, 1, 0),
(2, 'Admins', 0, 1, 0),
(3, 'Chiefs', 0, 1, 1),
(4, 'Users', 0, 0, 0);

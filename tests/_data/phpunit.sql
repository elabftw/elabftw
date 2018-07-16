--
-- elabftw mysql data for phpunit tests. This file is loaded for tests only.
--
-- @author Nicolas CARPi <nicolas.carpi@curie.fr>
-- @copyright 2012 Nicolas CARPi
-- @see https://www.elabftw.net Official website
-- @license AGPL-3.0
-- @package elabftw

--
-- Dumping data for table `experiments`
--
INSERT INTO `experiments` (`id`, `team`, `title`, `date`, `body`, `status`, `userid`, `elabid`, `locked`, `lockedby`, `lockedwhen`, `timestamped`, `timestampedby`, `timestamptoken`, `timestampedwhen`, `visibility`, `datetime`) VALUES
(1, 1, 'Untitled', 20160729, '<p><span style="font-size: 14pt;"><strong>Goal :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Procedure :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Results :</strong></span></p>\r\n<p>&nbsp;</p>', '1', 1, '20160729-01079f04e939ad08f44bda36c39faff65a83ef56', 0, NULL, NULL, 0, NULL, NULL, NULL, 'team', '2016-07-29 21:20:59');

--
-- Dumping data for table `idps`
--
INSERT INTO `idps` VALUES (1,'OneLogin','https://app.onelogin.com/','https://onelogin.com/','urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST','https://onelogin.com/','urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect','-----BEGIN CERTIFICATE-----\r\nMIIELDCCAxggAwIBAgIUaFt6ppX/TrAJo207cGFEJEdGaLgwDQYJKoZIhvcNAQEF\r\nBQAwXaELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYD\r\nVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAy\r\nOTU4MB4XDTE3MDMxOTExMzExNloXDTIyMDMyMDExMzExNlowXzELMAkGA1UEBhMC\r\nVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJ\r\nZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MIIBIjANBgkqhkiG\r\n9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzNKk3lhtLUJKvyl+0HZF3xpsjYRFT0HR30xA\r\nDhRUGT/7lwVl3SnkgN6Us6NtOdKRFqFntz37s4qkmbzD0tGG6GirIIvgFx8HKhTw\r\nYgjsMsC/+NcS854zB/9pDlwNpZwhjGXZgE9YQUXuiZp1W/1kE+KZANr1KJKjtlsi\r\nWjNWah9VXLKCjQfKHdgYxSiSW9mv/Phz6ZjW0M3wdnJQRGg0iUzDxWhYp7sGUvjI\r\nhPtdb+VCYVm2MymYESXbkXH60kG26TPvvJrELPkAJ54RWsuPkWADBZxIozeS/1He\r\nhjg2vIcH7T/x41+qSN9IzlhWQTYtVCkpR2ShNbXL7AUXMM5bsQIDAQABo4HfMIHc\r\nMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPERoVBCoadgrSI2Wdy7zPWIUuWyMIGc\r\nBgNVHSMEgZQwgZGAFPERoVBCoadgrSI2Wdy7zPWIUuWyoWOkYTBfMQswCQYDVQQG\r\nEwJVUzEXMBUGA1UECgwOSW5zdGl0dXQgQ3VyaWUxFTATBgNVBAsMDE9uZUxvZ2lu\r\nIElkUDEgMB4GA1UEAwwXT25lTG9naW4gQWNjb3VudCAxMDI5NTiCFGhbeqRV/06w\r\nCaNtO3BhRCRHRmi4MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEA\r\nZ7CjWWuRdwJFBsUyEewobXi/yYr/AnlmkjNDOJyDGs2DHNHVEmrm7z4LWmzLHWPf\r\nzAu4w55wovJg8jrjhTaFiBO5zcAa/3XQyI4atKKu4KDlZ6cM/2a14mURBhPT6I+Z\r\nZUVeX6411AgWQmohsESXmamEZtd89aOWfwlTFfAw8lbe3tHRkZvD5Y8N5oawvdHS\r\nurapSo8fde/oWUkO8I3JyyTUzlFOA6ri8bbnWz3YnofB5TXoOtdXui1SLuVJu8AB\r\nBEbhgv/m1o36VdOoikJjlZOUjfX5xjEupRkX/YTp0yfNmxt71kjgVLs66b1+dRG1\r\nc2Zk0y2rp0x3y3KG6K61Ug==\r\n-----END CERTIFICATE-----'),(2,'test idp','https://idp.example.org','https://idp.example.org','osef','https://idp.example.org','osef','xauirset');

--
-- Dumping data for table `experiments_revisions`
--

INSERT INTO `experiments_revisions` (`id`, `item_id`, `body`, `savedate`, `userid`) VALUES
(1, 1, '<p><span style="font-size: 14pt;"><strong>Goal :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Procedure :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Results :</strong></span></p>\r\n<p>&nbsp;</p>', '2016-07-29 21:21:11', 1);


--
-- Dumping data for table `experiments_templates`
--

INSERT INTO `experiments_templates` (`id`, `team`, `body`, `name`, `userid`, `ordering`) VALUES
(1, 1, '<p><span style="font-size: 14pt;"><strong>Goal :</strong></span></p>\n<p>&nbsp;</p>\n<p><span style="font-size: 14pt;"><strong>Procedure :</strong></span></p>\n<p>&nbsp;</p>\n<p><span style="font-size: 14pt;"><strong>Results :</strong></span></p><p>&nbsp;</p>', 'default', 0, NULL);


--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `team`, `title`, `date`, `body`, `rating`, `type`, `locked`, `userid`) VALUES
(1, 1, 'Database item 1', 20160729, '<p>Go to the admin panel to edit/add more items types!</p>', 0, 1, NULL, 1);

--
-- Dumping data for table `items_revisions`
--

INSERT INTO `items_revisions` (`id`, `item_id`, `body`, `savedate`, `userid`) VALUES
(1, 1, '<p>Go to the admin panel to edit/add more items types!</p>', '2016-07-29 21:21:22', 1);

--
-- Dumping data for table `items_types`
--

INSERT INTO `items_types` (`id`, `team`, `name`, `color`, `template`, `ordering`, `bookable`) VALUES
(1, 1, 'Edit me', '32a100', '<p>Go to the admin panel to edit/add more items types!</p>', NULL, 0);

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`id`, `team`, `name`, `color`, `is_timestampable`, `is_default`, `ordering`) VALUES
(1, 1, 'Running', '0096ff', 0, 1, NULL),
(2, 1, 'Success', '00ac00', 1, 0, NULL),
(3, 1, 'Need to be redone', 'c0c0c0', 1, 0, NULL),
(4, 1, 'Fail', 'ff0000', 1, 0, NULL);

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `deletable_xp`, `link_name`, `link_href`, `datetime`, `stamplogin`, `stamppass`, `stampprovider`, `stampcert`, `stamphash`) VALUES
(1, 'Editme', 1, 'Documentation', 'https://doc.elabftw.net', '2016-07-28 19:23:15', NULL, NULL, NULL, NULL, 'sha256');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userid`, `salt`, `password`, `team`, `usergroup`, `firstname`, `lastname`, `email`, `phone`, `cellphone`, `skype`, `website`, `register_date`, `token`, `limit_nb`, `sc_create`, `sc_edit`, `sc_submit`, `sc_todo`, `close_warning`, `chem_editor`, `validated`, `lang`) VALUES
(1, 'f84cf883e2c79fd8beceacf17d0b6e9fe98083e49e5f3cf949e30efa14e08a08b9b1b1e1a2e26dfbb7efd6158ffc6f405ed4669626a784ae8d76a8ec7bcf3f1d', 'a3120de3fbce90abd63c2a8ec81ebfe4e00849c56a89e1d3d196290a4b88ed81e8829e79fe50ceae05f52d6422485d29dda2d88b4932dca7bfb8efb7cbdb3745', 1, 1, 'Php', 'UNIT', 'phpunit@yopmail.com', NULL, NULL, NULL, NULL, 1469733882, '8873f66dfae374a3cce82f91621689cf', 15, 'c', 'e', 's', 't', 0, 0, 1, 'en_GB');

-- create a second team
INSERT INTO teams (team_name, link_name, link_href) VALUES ('Tata team', 'doc', 'http://doc.example.org');
-- create a second user
INSERT INTO users(
    `email`,
    `password`,
    `firstname`,
    `lastname`,
    `team`,
    `usergroup`,
    `salt`,
    `register_date`,
    `validated`,
    `lang`
        ) VALUES (
    'tata@yopmail.com',
    'osef',
    'tata',
    'TATA',
    '2',
    '1',
    'osef',
    '1503372272',
    '1',
    'en-GB');



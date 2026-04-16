/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

CREATE TABLE IF NOT EXISTS `#__livehelp_administration` (
  `id` INT (20) NOT NULL auto_increment,
  `user` bigint(20) NOT NULL default '0',
  `username` varchar(30) NOT NULL default '',
  `operator_id` INT (20) NOT NULL default '0',
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `message` text NOT NULL,
  `id_domain` INT (20) UNSIGNED  NULL,
  `align` int(1) NOT NULL default '0',
  `status` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__livehelp_accounts` (
  `id_account` INT(19) NOT NULL default '0',
  `id_account_type` INT(19) default '1',
  `login` varchar(30) NOT NULL default '',
  `password` varchar(30) NOT NULL default '',
  `creation_date` date default '0000-00-00',
  `expire_date` date default '0000-00-00',
  `status` char(1) NOT NULL default '0',
  `user_id` INT(20) NOT NULL default '0',
  PRIMARY KEY  (`id_account`),
  UNIQUE KEY `uk_accounts_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__livehelp_accounts_domain` (
  `id_account_domain` INT(20) NOT NULL auto_increment,
  `id_account` INT(20) NOT NULL default '0',
  `id_domain` INT(20) NOT NULL default '0',
  `status` INT(1) NOT NULL default '1',
  PRIMARY KEY  (`id_account_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__livehelp_commands` (
  `id` INT(5) NOT NULL auto_increment,
  `type` INT(1) NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  `contents` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__livehelp_domain_user` (
  `id_domain_user` INT(20) NOT NULL auto_increment,
  `id_domain` INT(20) NOT NULL default '0',
  `id_user` INT(20) NOT NULL default '0',
  `status` int(1) NOT NULL default '1',
  PRIMARY KEY  (`id_domain_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_domains` (
  `id_domain` INT(20) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `status` INT(1) NOT NULL default '1',
  `configuration` text NOT NULL,
  PRIMARY KEY  (`id_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_languages` (
  `code` char(2) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  `charset` varchar(100) NOT NULL default '',
  `installed` int(1) DEFAULT NULL,
  PRIMARY KEY  (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_languages_domain` (
  `Id_domain` INT(11) NOT NULL default '0',
  `code` char(2) NOT NULL default '',
  `name` varchar(100) NOT NULL default '', 
  `welcome_message` text NOT NULL,
  PRIMARY KEY  (`Id_domain`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_messages` (
  `id` INT(20) NOT NULL auto_increment,
  `session` INT(20) NOT NULL default '0',
  `username` varchar(30) NOT NULL default '',
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `message` text NOT NULL,
  `id_domain` INT(20) default NULL,
  `align` INT(1) NOT NULL default '0',
  `status` INT(1) NOT NULL default '0',
  `id_user` INT(20) default '-1',
  `delivered` INT(1) DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `idx_session` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_requests` (
  `id` INT(20) NOT NULL auto_increment,
  `ipaddress` varchar(100) NOT NULL default '',
  `useragent` varchar(200) NOT NULL default '',
  `resolution` varchar(20) NOT NULL default '',
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `request` datetime NOT NULL default '0000-00-00 00:00:00',
  `refresh` datetime NOT NULL default '0000-00-00 00:00:00',
  `url` text NOT NULL,
  `id_domain` INT(20) default NULL,
  `title` varchar(150) NOT NULL default '',
  `referrer` text NOT NULL,
  `path` text NOT NULL,
  `initiate` INT(20) NOT NULL default '0',
  `status` INT(1) NOT NULL default '0',
  `services` varchar(255) default NULL,
  `number_pages` int(11) NOT NULL default '0',
  `city` varchar(50) default NULL,
  `region` varchar(50) default NULL,
  `country_code` varchar(6) default NULL,
  `country` varchar(50) default NULL,
  `latitude` varchar(20) default NULL,
  `longitude` varchar(20) default NULL, 
  `visitor_name` varchar(30) default NULL,
  `visitor_email` varchar(50) default NULL,
  `visitor_id` INT(20) default NULL,
  `init_message` varchar(90) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `IDX_R_DOMAIN` (`id_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_responses` (
  `id` INT(5) NOT NULL auto_increment,
  `contents` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_sa_domain_user_role` (
  `id_domain_user_role` INT(19) NOT NULL auto_increment,
  `id_domain_user` INT(19) default '0',
  `id_role` INT(19) default NULL,
  PRIMARY KEY  (`id_domain_user_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_sa_role` (
  `id_role` INT(19) NOT NULL default '0',
  `description` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_sa_role_services` (
  `id_role_service` INT(19) NOT NULL default '0',
  `id_role` INT(19) default NULL,
  `id_service` INT(19) default NULL,
  PRIMARY KEY  (`id_role_service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_sessions` (
  `id` INT(20) NOT NULL auto_increment,
  `request` INT(20) NOT NULL default '0',
  `username` varchar(30) NOT NULL default '',
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `refresh` datetime NOT NULL default '0000-00-00 00:00:00',
  `email` varchar(50) NOT NULL default '',
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(30) DEFAULT NULL,
  `server` varchar(100) NOT NULL default '',
  `department` varchar(50) NOT NULL default '',
  `rating` INT(1) NOT NULL default '0',
  `typing` INT(1) NOT NULL default '0',
  `transfer` INT(1) NOT NULL default '0',
  `active` INT(1) NOT NULL default '0',
  `language` char(2) NOT NULL default '',
  `id_user` INT(20) default NULL,
  `id_domain` INT(20) default NULL,
  `id_agent` INT(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `IDX_R_SESSOION` (`request`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_ge_global_settings` (
  `id` varchar(50) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_settings` (
  `id` INT(20) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `id_domain` INT(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_statuses` (
  `id_status` INT(11) NOT NULL default '0',
  `id_service` INT(11) NOT NULL default '0',
  `service_name` varchar(100) default '',
  `service_description` text,
  PRIMARY KEY  (`id_service`,`id_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_users` (
  `id` INT(20) NOT NULL auto_increment,
  `username` varchar(50) NOT NULL default '',
  `password` varchar(100) NOT NULL default '',
  `firstname` varchar(50) NOT NULL default '',
  `lastname` varchar(50) NOT NULL default '',
  `email` varchar(50) NOT NULL default '',
  `department` varchar(100) NOT NULL default '',
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `refresh` datetime NOT NULL default '0000-00-00 00:00:00',
  `disabled` INT(1) NOT NULL default '0',
  `privilege` INT(1) NOT NULL default '0', 
  `photo` varchar(10) DEFAULT NULL,
  `status` INT(20) NOT NULL default '0',
  `answers` INT(1) default '1',
  `schedule` INT(1) DEFAULT '0',
  `initial_time` time DEFAULT NULL,
  `final_time` time DEFAULT NULL,
  `device` varchar(20) DEFAULT NULL,
  `device_id` varchar(200) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uk_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS  `#__livehelp_offline_messages` (
  `id` INT(20) NOT NULL auto_increment,
  `name` varchar(30) NOT NULL default '',
  `email` varchar(30) NOT NULL default '',
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(30) DEFAULT NULL,
  `message` text NOT NULL,
  `id_domain` INT(20) NOT NULL,
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',  
  `answered` char(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_countries` (
  `code` varchar(2) NOT NULL,
  `name` char(64) NOT NULL,
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__livehelp_not_allowed_countries` (
  `id` INT(20) NOT NULL AUTO_INCREMENT,
  `id_domain` INT(11) NOT NULL DEFAULT '0',
  `code` varchar(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS`#__livehelp_core_settings` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE INDEX IDX_R_REQUEST ON #__livehelp_requests (refresh);

/* Inserts */

/*!40000 ALTER TABLE #__livehelp_accounts DISABLE KEYS */;
INSERT INTO #__livehelp_accounts VALUES (1, 1, 'default', 'd16dcdb233ba1ecfb72b3d903e1ea2', '2006-03-22', '2006-03-22', '1', 1);
/*!40000 ALTER TABLE #__livehelp_accounts ENABLE KEYS */;

/*!40000 ALTER TABLE #__livehelp_languages DISABLE KEYS */;
insert into `#__livehelp_languages`(`code`,`name`,`charset`, `installed`) values ('en','English','utf-8','1')
, ('sp','Spanish','utf-8','1')
, ('de','Deutsch','utf-8','1')
, ('pt','Portuguese','utf-8','1')
, ('it','Italian','utf-8','1')
, ('fr','French','utf-8','1')
, ('cz','Czech','utf-8','0')
, ('se','Swedish','utf-8','0')
, ('no','Norwegian','utf-8','0')
, ('tr','Turkey','utf-8','0')
, ('gr','Greek','utf-8','0')
, ('he','Hebrew','utf-8','0')
, ('fa','Farsi','utf-8','0')
, ('sr','Serbian','utf-8','0')
, ('ru','Rusian','utf-8','0')
, ('hu','Hungarian','utf-8','0')
, ('zh','Traditional Chinese','utf-8','0')
, ('cn','Simplified Chinese','utf-8','0')
, ('ar','Arab','utf-8','0')
, ('nl','Dutch','utf-8','0')
, ('fi','Finnish','utf-8','0')
, ('dk','Danish','utf-8','0')
, ('pl','Polish','utf-8','0')
, ('bg','Bulgarian','utf-8','0')
, ('sk','Slovak','utf-8','0')
, ('cr','Croatian','utf-8','0')
, ('id','Indonesian','utf-8','0')
, ('lt','Lithuanian','utf-8','0')
, ('ro','Romanian','utf-8','0')
, ('sl','Slovenian','utf-8','0')
, ('et','Estonian','utf-8','0')
, ('lv','Latvian','utf-8','0')
, ('ge','Georgian','utf-8','0')
, ('jp','Japanese','utf-8','0');

/*!40000 ALTER TABLE #__livehelp_languages ENABLE KEYS */;

/*!40000 ALTER TABLE #__livehelp_sa_role DISABLE KEYS */;
INSERT INTO #__livehelp_sa_role VALUES (1, 'superuser');
INSERT INTO #__livehelp_sa_role VALUES (2, 'LiveChat administrator');
INSERT INTO #__livehelp_sa_role VALUES (3, 'LiveCall administrator');
INSERT INTO #__livehelp_sa_role VALUES (5, 'LiveTalk administrator');
INSERT INTO #__livehelp_sa_role VALUES (6, 'Web flow administrator');
INSERT INTO #__livehelp_sa_role VALUES (7, 'LiveMail');
/*!40000 ALTER TABLE #__livehelp_sa_role ENABLE KEYS */;

/*!40000 ALTER TABLE #__livehelp_sa_role_services DISABLE KEYS */;
INSERT INTO #__livehelp_sa_role_services VALUES (1, 1, 1);
INSERT INTO #__livehelp_sa_role_services VALUES (7, 2, 1);
/*!40000 ALTER TABLE #__livehelp_sa_role_services ENABLE KEYS */;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
INSERT INTO #__livehelp_ge_global_settings VALUES ('webcall_timeout', '30');

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;

/*!40000 ALTER TABLE #__livehelp_settings DISABLE KEYS */;

insert into `#__livehelp_settings`(`id`,`name`,`value`,`id_domain`) values (23,'admin_homepage','/eserver1/panel/visitors_index.php',0)
, (22,'timezone','+1000',0)
, (21,'default_department','General',0)
, (20,'departments','1',0)
, (19,'disable_offline_email','0',0)
, (18,'disable_login_details','0',0)
, (17,'admin_chat_font_size','12px',0)
, (16,'guest_chat_font_size','12px',0)
, (15,'background_color','#F9F9F9',0)
, (14,'font_link_color','#333399',0)
, (13,'received_font_color','#000000',0)
, (12,'sent_font_color','#666666',0)
, (11,'chat_font_type','Arial, Arial Unicode, Lucida, Verdana',0)
, (10,'font_color','#000000',0)
, (9,'font_size','13px',0)
, (8,'font_type','Arial, Helvetica, sans-serif,Verdana',0)
, (7,'admin_smilies','0',0)
, (6,'guest_smilies','1',0)
, (5,'livehelp_logo','eserver/i18n/sp/pictures/help_logo.gif',0)
, (4,'livehelp_name','www.activehelper.com Live Help',0)
, (3,'offline_email','support@activehelper.com',0)
, (2,'site_address','http://www.activehelper.com',0)
, (1,'site_name','www.activehelper.com',0)
, (24,'initiate_chat_valign','top',0)
, (25,'initiate_chat_halign','right',0)
, (26,'disable_chat_username','0',0)
, (27,'campaign_image','chat_banner.gif',0)
, (28,'campaign_link','http://www.activehelper.com/',0)
, (29,'disable_popup_help','1',0)
, (30,'p3p','ALL DSP COR CUR OUR IND ONL UNI COM NAV',0)
, (31,'require_guest_details','0',0)
, (32,'configure_smtp','0',0)
, (33,'smtp_server','',0)
, (34,'smtp_port','25',0)
, (35,'from_email','support@activehelper.com',0)
, (36,'login_timeout','20',0)
, (37,'chat_background_img','activehelper',0)
, (38,'chat_invitation_img','initiate_dialog.gif',0)
, (39,'chat_button_img','send.gif',0)
, (40,'chat_button_hover_img','send_hover.gif',0)
, (41,'custom_offline_form_link','',0)
, (42,'log_offline_email','1',0)
, (43,'disable_language','0',0)
, (44,'company_logo','logo.jpg',0)
, (45,'company_link','http://www.activehelper.com',0)
, (46,'disable_copyright','1',0)
, (47,'company_slogan','ACTIVEHELPER Platform All Rights Reserved',0)
, (48,'copyright_image','1',0)
, (49,'analytics_account','',0)
, (50,'invitation_refresh','0',0)
, (51,'disable_invitation','0',0)
, (52,'disable_geolocation','0',0)
, (53,'disable_tracking_offline','0',0)
, (54,'captcha','1',0)
, (55,'disable_agent_bannner','0',0)
, (56,'company','0',0)
, (57,'phone','0',0);

/*!40000 ALTER TABLE #__livehelp_settings ENABLE KEYS */;

/*!40000 ALTER TABLE #__livehelp_statuses DISABLE KEYS */;
INSERT INTO #__livehelp_statuses VALUES (0, 4, 'Waiting', 'Just addes request without answer');
INSERT INTO #__livehelp_statuses VALUES (-1, 4, 'Canceled', 'Canceled by user');
INSERT INTO #__livehelp_statuses VALUES (-2, 4, 'Timeout', 'Canceled by Timeout');
INSERT INTO #__livehelp_statuses VALUES (1, 4, 'Talking', 'Operator is talking now with user');
INSERT INTO #__livehelp_statuses VALUES (2, 4, 'Finished', 'Finished');
/*!40000 ALTER TABLE #__livehelp_statuses ENABLE KEYS */;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
insert into `#__livehelp_countries`(`code`,`name`) values ('AD','Andorra')
, ('AE','United Arab Emirates')
, ('AF','Afghanistan')
, ('AG','Antigua and Barbuda')
, ('AI','Anguilla')
, ('AL','Albania')
, ('AM','Armenia')
, ('AN','Netherlands Antilles')
, ('AO','Angola')
, ('AP','Asia/Pacific Region')
, ('AQ','Antartica')
, ('AR','Argentina')
, ('AS','American Samoa')
, ('AT','Austria')
, ('AU','Australia')
, ('AW','Aruba')
, ('AX','Aland Islands')
, ('AZ','Azerbaijan')
, ('BA','Bosnia and Herzegovina')
, ('BB','Barbados')
, ('BD','Bangladesh')
, ('BE','Belgium')
, ('BF','Burkina Faso')
, ('BG','Bulgaria')
, ('BH','Bahrain')
, ('BI','Burundi')
, ('BJ','Benin')
, ('BM','Bermuda')
, ('BN','Brunei Darussalam')
, ('BO','Bolivia')
, ('BR','Brazil')
, ('BS','Bahamas')
, ('BT','Bhutan')
, ('BV','Bouvet Island')
, ('BW','Botswana')
, ('BY','Belarus')
, ('BZ','Belize')
, ('CA','Canada')
, ('CC','Cocos (Keeling) Islands')
, ('CD','Congo  The Democratic Republic of the')
, ('CF','Central African Republic')
, ('CG','Congo')
, ('CH','Switzerland')
, ('CI','Cote d Ivoire')
, ('CK','Cook Islands')
, ('CL','Chile')
, ('CM','Cameroon')
, ('CN','China')
, ('CO','Colombia')
, ('CR','Costa Rica')
, ('CU','Cuba')
, ('CV','Cape Verde')
, ('CX','Christmas Island')
, ('CY','Cyprus')
, ('CZ','Czech Republic')
, ('DE','Germany')
, ('DJ','Djibouti')
, ('DK','Denmark')
, ('DM','Dominica')
, ('DO','Dominican Republic')
, ('DZ','Algeria')
, ('EC','Ecuador')
, ('EE','Estonia')
, ('EG','Egypt')
, ('EH','Western Sahara')
, ('ER','Eritrea')
, ('ES','Spain')
, ('ET','Ethiopia')
, ('EU','Europe')
, ('FI','Finland')
, ('FJ','Fiji')
, ('FK','Falkland Islands (Malvinas)')
, ('FM','Micronesia  Federated States of')
, ('FO','Faroe Islands')
, ('FR','France')
, ('GA','Gabon')
, ('GB','United Kingdom')
, ('GD','Grenada')
, ('GE','Georgia')
, ('GF','French Guiana')
, ('GG','Guernsey')
, ('GH','Ghana')
, ('GI','Gibraltar')
, ('GL','Greenland')
, ('GM','Gambia')
, ('GN','Guinea')
, ('GP','Guadeloupe')
, ('GQ','Equatorial Guinea')
, ('GR','Greece')
, ('GS','South Georgia and the South Sandwich Islands')
, ('GT','Guatemala')
, ('GU','Guam')
, ('GW','Guinea-Bissau')
, ('GY','Guyana')
, ('HK','Hong Kong')
, ('HM','Heard Island and McDonald Islands')
, ('HN','Honduras')
, ('HR','Croatia')
, ('HT','Haiti')
, ('HU','Hungary')
, ('ID','Indonesia')
, ('IE','Ireland')
, ('IL','Israel')
, ('IM','Isle of Man')
, ('IN','India')
, ('IO','British Indian Ocean Territory')
, ('IQ','Iraq')
, ('IR','Iran  Islamic Republic of')
, ('IS','Iceland')
, ('IT','Italy')
, ('JE','Jersey')
, ('JM','Jamaica')
, ('JO','Jordan')
, ('JP','Japan')
, ('KE','Kenya')
, ('KG','Kyrgyzstan')
, ('KH','Cambodia')
, ('KI','Kiribati')
, ('KM','Comoros')
, ('KN','Saint Kitts and Nevis')
, ('KP','Korea  Democratic People Republic of')
, ('KR','Korea  Republic of')
, ('KW','Kuwait')
, ('KY','Cayman Islands')
, ('KZ','Kazakhstan')
, ('LA','Lao People  Democratic Republic')
, ('LB','Lebanon')
, ('LC','Saint Lucia')
, ('LI','Liechtenstein')
, ('LK','Sri Lanka')
, ('LR','Liberia')
, ('LS','Lesotho')
, ('LT','Lithuania')
, ('LU','Luxembourg')
, ('LV','Latvia')
, ('LY','Libyan Arab Jamahiriya')
, ('MA','Morocco')
, ('MC','Monaco')
, ('MD','Moldova  Republic of')
, ('ME','Montenegro')
, ('MG','Madagascar')
, ('MH','Marshall Islands')
, ('MK','Macedonia')
, ('ML','Mali')
, ('MM','Myanmar')
, ('MN','Mongolia')
, ('MO','Macao')
, ('MP','Northern Mariana Islands')
, ('MQ','Martinique')
, ('MR','Mauritania')
, ('MS','Montserrat')
, ('MT','Malta')
, ('MU','Mauritius')
, ('MV','Maldives')
, ('MW','Malawi')
, ('MX','Mexico')
, ('MY','Malaysia')
, ('MZ','Mozambique')
, ('NA','Namibia')
, ('NC','New Caledonia')
, ('NE','Niger')
, ('NF','Norfolk Island')
, ('NG','Nigeria')
, ('NI','Nicaragua')
, ('NL','Netherlands')
, ('NO','Norway')
, ('NP','Nepal')
, ('NR','Nauru')
, ('NU','Niue')
, ('NZ','New Zealand')
, ('OM','Oman')
, ('PA','Panama')
, ('PE','Peru')
, ('PF','French Polynesia')
, ('PG','Papua New Guinea')
, ('PH','Philippines')
, ('PK','Pakistan')
, ('PL','Poland')
, ('PM','Saint Pierre and Miquelon')
, ('PN','Pitcairn')
, ('PR','Puerto Rico')
, ('PS','Palestinian Territory')
, ('PT','Portugal')
, ('PW','Palau')
, ('PY','Paraguay')
, ('QA','Qatar')
, ('RE','Reunion')
, ('RO','Romania')
, ('RS','Serbia')
, ('RU','Russian Federation')
, ('RW','Rwanda')
, ('SA','Saudi Arabia')
, ('SB','Solomon Islands')
, ('SC','Seychelles')
, ('SD','Sudan')
, ('SE','Sweden')
, ('SG','Singapore')
, ('SH','Saint Helena')
, ('SI','Slovenia')
, ('SJ','Svalbard and Jan Mayen')
, ('SK','Slovakia')
, ('SL','Sierra Leone')
, ('SM','San Marino')
, ('SN','Senegal')
, ('SO','Somalia')
, ('SR','Suriname')
, ('ST','Sao Tome and Principe')
, ('SV','El Salvador')
, ('SY','Syrian Arab Republic')
, ('SZ','Swaziland')
, ('TC','Turks and Caicos Islands')
, ('TD','Chad')
, ('TF','French Southern Territories')
, ('TG','Togo')
, ('TH','Thailand')
, ('TJ','Tajikistan')
, ('TK','Tokelau')
, ('TL','Timor-Leste')
, ('TM','Turkmenistan')
, ('TN','Tunisia')
, ('TO','Tonga')
, ('TR','Turkey')
, ('TT','Trinidad and Tobago')
, ('TV','Tuvalu')
, ('TW','Taiwan')
, ('TZ','Tanzania  United Republic of')
, ('UA','Ukraine')
, ('UG','Uganda')
, ('UM','United States Minor Outlying Islands')
, ('US','United States')
, ('UY','Uruguay')
, ('UZ','Uzbekistan')
, ('VA','Holy See (Vatican City State)')
, ('VC','Saint Vincent and the Grenadines')
, ('VE','Venezuela')
, ('VG','Virgin Islands  British')
, ('VI','Virgin Islands  U.S.')
, ('VN','Vietnam')
, ('VU','Vanuatu')
, ('WF','Wallis and Futuna')
, ('WS','Samoa')
, ('YE','Yemen')
, ('YT','Mayotte')
, ('ZA','South Africa')
, ('ZM','Zambia')
, ('ZW','Zimbabwe');

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;

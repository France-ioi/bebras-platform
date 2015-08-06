CREATE TABLE IF NOT EXISTS `tm_platforms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL,
  `pc_key` varchar(5000) NOT NULL,
  `pv_key` varchar(5000) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tm_platform_uri` (`uri`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__gurupayment_wtguruyookassa`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_order_id` int(11),
  `yookassa_payment_id` varchar(40),
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;
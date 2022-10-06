<?php
/**
 * Copyright (C) 2022  Jaap Jansma (jaap.jansma@civicoop.org)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['recipients'] = [];
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_text'] = array(
  'product_name',
  'product_sku',
);
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_subject'] = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_text'];
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_html'] = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_text'];
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_replyTo'] = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['recipients'];
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_recipient_cc'] = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['recipients'];
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['email_recipient_bcc'] = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['jvh_isotope_stock_bundle']['jvh_isotope_product_out_of_stock']['recipients'];
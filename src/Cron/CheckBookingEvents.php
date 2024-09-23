<?php
/**
 * Copyright (C) 2024  Jaap Jansma (jaap.jansma@civicoop.org)
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

namespace JvH\JvHIsotopeStockBundle\Cron;

use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\System;
use Isotope\Model\Product;
use Krabo\IsotopeStockBundle\Event\Events;
use Krabo\IsotopeStockBundle\Event\ManualBookingEvent;
use Krabo\IsotopeStockBundle\Helper\BookingHelper;
use Krabo\IsotopeStockBundle\Model\BookingModel;

/**
 * @CronJob("minutely")
 */
class CheckBookingEvents
{

    /**
     * @param \Contao\CoreBundle\Framework\ContaoFramework $contaoFramework
     */
    public function __construct(ContaoFramework $contaoFramework)
    {
        $contaoFramework->initialize();
    }

    public function __invoke(): void
    {
        $config = System::getContainer()->getParameter('jvh.jvh_isotope_stock.config');
        $types = $config['mass_booking_config'];
        /** @var Database $db */
        $db = System::importStatic('Database');
        $objResult = $db->execute("SELECT * FROM `tl_isotope_stock_jvh_booking_event` LIMIT 0, 1");
        $ids = [];
        while($objResult->next()) {
            $ids[] = $objResult->id;
            $booking = BookingModel::findByPk($objResult->booking_id);
            $product = Product::findByPk($booking->product_id);
            if ($product === null) {
              continue;
            }
            $type = null;
            foreach($types as $t) {
                if ($t['type'] == $booking->type) {
                    $type = $t;
                    break;
                }
            }

            if ($type['is_pre_order_delivery']) {
                \Database::getInstance()->prepare("
                    UPDATE `tl_isotope_stock_booking_line` `line` 
                    INNER JOIN `tl_isotope_stock_booking` `booking`
                    SET `line`.`account` = ?
                    WHERE `line`.`account` = ?
                    AND `booking`.`product_id` = ?
                    AND `booking`.`period_id` = ?
                ")->execute($config['sales_account_id'], $config['pre_order_sales_account_id'], $product->id, $booking->period_id);
                $product->isostock_preorder = '0';
                $product->save();
            }
            BookingHelper::updateBalanceStatusForBooking($booking->id);
            $event = new ManualBookingEvent($booking);
            System::getContainer()
                ->get('event_dispatcher')
                ->dispatch($event, Events::MANUAL_BOOKING_EVENT);
        }
        if (count($ids)) {
            $sql = "DELETE FROM `tl_isotope_stock_jvh_booking_event` WHERE `id` IN (" . implode(", ", $ids) . ")";
            $db->execute($sql);
        }
    }
}
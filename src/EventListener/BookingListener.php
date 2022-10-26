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

namespace JvH\JvHIsotopeStockBundle\EventListener;

use Contao\Email;
use Contao\System;
use Isotope\Model\Product;
use Krabo\IsotopePackagingSlipBundle\Model\IsotopePackagingSlipModel;
use Krabo\IsotopePackagingSlipBundle\Model\IsotopePackagingSlipProductCollectionModel;
use Krabo\IsotopeStockBundle\Event\BookingEvent;
use Krabo\IsotopeStockBundle\Event\ClearBookingEvent;
use Krabo\IsotopeStockBundle\Event\Events;
use Krabo\IsotopeStockBundle\Event\ManualBookingEvent;
use Krabo\IsotopeStockBundle\Helper\ProductHelper;
use Krabo\IsotopeStockBundle\Model\BookingModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BookingListener implements EventSubscriberInterface {

  private static $alreadyNotifiedProducts = [];

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * ['eventName' => 'methodName']
   *  * ['eventName' => ['methodName', $priority]]
   *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
   *
   * The code must not depend on runtime state as it will only be called at
   * compile time. All logic depending on runtime state must be put into the
   * individual methods handling the events.
   *
   * @return array<string, mixed> The event names to listen to
   */
  public static function getSubscribedEvents() {
    return [
      Events::BOOKING_EVENT => 'onBooking',
      Events::MANUAL_BOOKING_EVENT => 'onManualBooking',
      Events::CLEAR_BOOKING_EVENT => 'onClearBooking',
    ];
  }

  public function onClearBooking(ClearBookingEvent $event) {
    if (!empty($event->productId)) {
      static::disableNotificationForProduct($event->productId);
    }

    if (isset($event->extraData['packaging_slip_id'])) {
      $packagingSlipProducts = IsotopePackagingSlipProductCollectionModel::findBy('pid', $event->extraData['packaging_slip_id']);
      if ($packagingSlipProducts) {
        while ($packagingSlipProducts->next()) {
          static::disableNotificationForProduct($packagingSlipProducts->product_id);
        }
      }
    }
  }

  public function onBooking(BookingEvent $event) {
    if ($event->bookingModel->type == BookingModel::SALES_TYPE) {
      $this->checkStock($event->bookingModel);
    }
  }

  public function onManualBooking(ManualBookingEvent $event) {
    if ($event->bookingModel->type == BookingModel::SALES_TYPE) {
      $this->checkStock($event->bookingModel);
    }
  }

  /**
   * @param int $productId
   * @return void
   */
  private static function disableNotificationForProduct(int $productId) {
    if (!ProductHelper::isProductAvailableToOrder($productId) && !in_array($productId, static::$alreadyNotifiedProducts)) {
      static::$alreadyNotifiedProducts[] = $productId;
    }
  }

  private function checkStock(BookingModel $bookingModel) {
    if (!ProductHelper::isProductAvailableToOrder($bookingModel->product_id) && !in_array($bookingModel->product_id, static::$alreadyNotifiedProducts)) {
      $objProduct = Product::findByPk($bookingModel->product_id);
      if ($objProduct) {
        static::$alreadyNotifiedProducts[] = $bookingModel->product_id;
        $objNotificationCollection = \NotificationCenter\Model\Notification::findBy('type', 'jvh_isotope_product_out_of_stock');
        $arrTokens = [];
        $arrTokens['product_name'] = $objProduct->name;
        $arrTokens['product_sku'] = $objProduct->sku;
        if (NULL !== $objNotificationCollection) {
          foreach($objNotificationCollection as $objNotification) {
            $objNotification->send($arrTokens);
          }
        }
      }
    }
  }


}
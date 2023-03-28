<?php
/**
 * Copyright (C) 2023  Jaap Jansma (jaap.jansma@civicoop.org)
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

use Contao\FrontendTemplate;
use Contao\PageModel;
use Haste\Input\Input;
use Isotope\Frontend;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Model\Product;
use Isotope\Model\Product\AbstractProduct;
use Isotope\RequestCache\CategoryFilter;
use Krabo\IsotopeStockBundle\Helper\ProductHelper;


class ProductCollectionListener {

  /**
   * @param array $arrBuffer
   * @param array $arrProducts
   * @param $template
   * @param $module
   *
   * @return array
   */
  public function generateProductList(array $arrBuffer, array $arrProducts, FrontendTemplate $template, $module) {
    $productIds = [];
    foreach($arrProducts as $objProduct) {
      $productIds[] = $objProduct->id;
    }
    if (count($productIds)) {
      ProductHelper::loadStockInfoForProducts($productIds);
    }
    $template->checkAvailaibility = true;
    foreach($arrBuffer as $index => $buffer) {
      $arrBuffer[$index]['html'] = $buffer['product']->generate(static::getProductConfig($buffer['product'], $module));
    }
    return $arrBuffer;
  }

  protected static function getProductConfig(IsotopeProduct $product, $module)
  {
    $type = $product->getType();

    return array(
      'module'         => $module,                                                                                                                                                                     'module'         => $module,
      'template'       => $module->iso_list_layout ?: $type->list_template,
      'gallery'        => $module->iso_gallery ?: $type->list_gallery,
      'buttons'        => $module->iso_buttons,
      'useQuantity'    => $module->iso_use_quantity,
      'disableOptions' => $module->iso_disable_options,
      'jumpTo'         => static::findJumpToPage($product, $module),
      'checkAvailaibility' => '1',
    );
  }

  /**
   * Find jumpTo page for current category scope
   *
   * @param IsotopeProduct $objProduct
   *
   * @return \PageModel
   */
  protected static function findJumpToPage(IsotopeProduct $objProduct, $module)
  {
    global $objPage;
    global $objIsotopeListPage;

    $productCategories = $objProduct instanceof AbstractProduct ? $objProduct->getCategories(true) : [];
    $arrCategories = array();

    if (!$module->iso_link_primary) {
      $arrCategories = array_intersect(
        $productCategories,
        static::findCategories($module)
      );
    }

    // If our current category scope does not match with any product category,
    // use the first allowed product category in the current root page
    if (empty($arrCategories)) {
      $arrCategories = $productCategories;
    }

    $arrCategories = Frontend::getPagesInCurrentRoot(
      $arrCategories,
      \FrontendUser::getInstance()
    );

    if (!empty($arrCategories)
      && ($objCategories = \PageModel::findMultipleByIds($arrCategories)) !== null
    ) {
      $blnMoreThanOne = $objCategories->count() > 1;
      foreach ($objCategories as $objCategory) {

        if ($objCategory->alias == 'index'
          && $blnMoreThanOne
        ) {
          continue;
        }

        return $objCategory;
      }
    }

    return $objIsotopeListPage ? : $objPage;
  }

  /**
   * The ids of all pages we take care of. This is what should later be used eg. for filter data.
   *
   * @return array|null
   */
  protected static function findCategories($module)
  {
    if (null !== $module->arrCategories) {
      return $module->arrCategories;
    }

    if ($module->defineRoot && $module->rootPage > 0) {
      $objPage = PageModel::findWithDetails($module->rootPage);
    } else {
      global $objPage;
    }

    $t = PageModel::getTable();
    $arrCategories = null;
    $strWhere = "$t.type!='error_403' AND $t.type!='error_404'";

    if (!BE_USER_LOGGED_IN) {
      $time = \Date::floorToMinute();
      $strWhere .= " AND ($t.start='' OR $t.start<'$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
    }

    switch ($module->iso_category_scope) {
      case 'global':
        $arrCategories = [$objPage->rootId];
        $arrCategories = \Database::getInstance()->getChildRecords($objPage->rootId, 'tl_page', false, $arrCategories, $strWhere);
        break;

      case 'current_and_first_child':
        $arrCategories   = \Database::getInstance()->execute("SELECT id FROM tl_page WHERE pid={$objPage->id} AND $strWhere")->fetchEach('id');
        $arrCategories[] = $objPage->id;
        break;

      case 'current_and_all_children':
        $arrCategories = [$objPage->id];
        $arrCategories = \Database::getInstance()->getChildRecords($objPage->id, 'tl_page', false, $arrCategories, $strWhere);
        break;

      case 'parent':
        $arrCategories = [$objPage->pid];
        break;

      case 'product':
        /** @var \Isotope\Model\Product\Standard $objProduct */
        $objProduct = Product::findAvailableByIdOrAlias(Input::getAutoItem('product'));
        $arrCategories = [0];

        if ($objProduct !== null) {
          $arrCategories = $objProduct->getCategories(true);
        }
        break;

      case 'article':
        $arrCategories = array($GLOBALS['ISO_CONFIG']['current_article']['pid'] ? : $objPage->id);
        break;

      case '':
      case 'current_category':
        $arrCategories = [$objPage->id];
        break;

      default:
        if (isset($GLOBALS['ISO_HOOKS']['findCategories']) && \is_array($GLOBALS['ISO_HOOKS']['findCategories'])) {
          foreach ($GLOBALS['ISO_HOOKS']['findCategories'] as $callback) {
            $arrCategories = \System::importStatic($callback[0])->{$callback[1]}($module);

            if ($arrCategories !== false) {
              break;
            }
          }
        }
        break;
    }

    $module->arrCategories = empty($arrCategories) ? array(0) : array_map('intval', $arrCategories);

    return $module->arrCategories;
  }

}
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

namespace JvH\JvHIsotopeStockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface  {

  /**
   * Generates the configuration tree builder.
   *
   * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree
   *   builder
   */
  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder('jvh-isotope-stock');
    $treeBuilder->getRootNode()
      ->children()
        ->scalarNode('sales_account_id')->end()
        ->scalarNode('pre_order_sales_account_id')->end()
        ->arrayNode('mass_booking_config')
          ->arrayPrototype()
            ->children()
              ->scalarNode('label')->end()
              ->scalarNode('type')->end()
              ->scalarNode('debit_id')->end()
              ->scalarNode('credit_id')->end()
              ->booleanNode('is_pre_order_delivery')->end()
            ->end()
          ->end()
        ->end()
      ->end();
    return $treeBuilder;
  }

}
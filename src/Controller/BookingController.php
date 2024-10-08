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

namespace JvH\JvHIsotopeStockBundle\Controller;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\System;
use Isotope\Model\Product;
use Krabo\IsotopeStockBundle\Event\Events;
use Krabo\IsotopeStockBundle\Event\ManualBookingEvent;
use Krabo\IsotopeStockBundle\Form\Type\AccountType;
use Krabo\IsotopeStockBundle\Form\Type\ProductSkuQuantityType;
use Krabo\IsotopeStockBundle\Helper\BookingHelper;
use Krabo\IsotopeStockBundle\Helper\ProductHelper;
use Krabo\IsotopeStockBundle\Model\BookingLineModel;
use Krabo\IsotopeStockBundle\Model\BookingModel;
use Krabo\IsotopeStockBundle\Model\PeriodModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

class BookingController extends AbstractController
{

  /**
   * @var \Twig\Environment
   */
  private $twig;

  /**
   * @var ContaoCsrfTokenManager
   */
  private $tokenManager;

  /**
   * @var string
   */
  private $csrfTokenName;

  public function __construct(TwigEnvironment $twig, ContaoCsrfTokenManager $tokenManager)
  {
    $this->twig = $twig;
    $this->tokenManager = $tokenManager;
    $this->csrfTokenName = System::getContainer()->getParameter('contao.csrf_token_name');
  }

  /**
   * @Route("/contao/tl_isotope_stock_booking/jvh_mass_booking",
   *     name="tl_isotope_stock_booking_jvh_mass_booking",
   *     defaults={"_scope": "backend", "_token_check": true}
   * )
   */
  public function massBooking(Request $request): Response
  {
    $config = System::getContainer()->getParameter('jvh.jvh_isotope_stock.config');
    $GLOBALS['TL_JAVASCRIPT'][] = 'assets/jquery/js/jquery.min.js|static';
    $response = new Response();
    \Contao\System::loadLanguageFile(BookingModel::getTable());
    \Contao\System::loadLanguageFile(PeriodModel::getTable());
    $periods = PeriodModel::findAll();

    $defaultData['period_id'] = PeriodModel::getFirstActivePeriod();
    $defaultData['date'] = new \DateTime();

    $formBuilder = $this->createFormBuilder($defaultData);
    $formBuilder->add('date', DateType::class, [
      'label' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['date'][0],
      'widget' => 'single_text',
      'input_format' => 'y-m-d',
      'html5' => true,
      'attr' => [
        'class' => 'tl_text',
      ],
      'row_attr' => [
        'class' => 'w50 widget'
      ]
    ]);
    $formBuilder->add('period_id',ChoiceType::class, [
      'label' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['period_id'][0],
      'choices' => $periods,
      'choice_label' => function(?PeriodModel $period) {
        $suffix = '';
        if ($period && !$period->active) {
          $suffix = ' (' . $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['period_inactive'] . ')';
        }
        return $period ? html_entity_decode($period->title) . $suffix : '';
      },
      'placeholder' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['period_placeholder'],
      'attr' => [
        'class' => 'tl_select',
      ],
      'row_attr' => [
        'class' => 'w50 widget'
      ]
    ]);
    $formBuilder->add('description', TextType::class, [
      'label' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['description'][0],
      'attr' => [
        'class' => 'tl_text',
      ],
      'row_attr' => [
        'class' => 'widget'
      ],
    ]);
    $types = $config['mass_booking_config'];

    $formBuilder->add('type', ChoiceType::class, [
      'choices' => array_keys($types),
      'choice_label' => function($choice, $key, $value) use ($types) {
        return $types[$choice]['label'];
      },
      'label' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['type'][0],
      'expanded' => true,
      'multiple' => false,
      'choice_attr' => [
        'class' => 'tl_radio',
      ],
      'row_attr' => [
        'class' => 'w50 widget'
      ],
    ]);

    $formBuilder->add('product_ids', CollectionType::class, [
      'entry_type' => ProductSkuQuantityType::class,
      'label' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['product_ids'],
      'allow_add' => true,
      'prototype' => true,
      'entry_options' => [
        'attr' => ['class' => 'product_id-box'],
      ],
    ]);

    $formBuilder->add('save', SubmitType::class, [
      'label' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['saveMassBooking'],
      'attr' => [
        'class' => 'tl_submit',
      ]
    ]);
    $formBuilder->add('REQUEST_TOKEN', HiddenType::class, [
      'data' => $this->tokenManager->getToken($this->csrfTokenName)
    ]);

    $form = $formBuilder->getForm();
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $insertIds = [];
      $data = $form->getData();
      $type = $types[$data['type']];
      foreach($data['product_ids'] as $product_id) {
        $product = Product::findOneBy('sku', $product_id['sku']);
        if ($product) {
          $booking = new BookingModel();
          $booking->description = $data['description'];
          $booking->date = $data['date']->getTimestamp();
          $booking->period_id = $data['period_id']->id;
          $booking->product_id = $product->id;
          $booking->type = $type['type'];
          $booking->save();
          $debitBookingLine = new BookingLineModel();
          $debitBookingLine->debit = $product_id['quantity'];
          $debitBookingLine->account = $type['debit_id'];
          $debitBookingLine->pid = $booking->id;
          $debitBookingLine->save();
          $creditBookingLine = new BookingLineModel();
          $creditBookingLine->credit = $product_id['quantity'];
          $creditBookingLine->account = $type['credit_id'];
          $creditBookingLine->pid = $booking->id;
          $creditBookingLine->save();
          $insertIds[] = "(" . $booking->id . ", " . time() .")";
        }
      }
      if (count($insertIds)) {
          \Database::getInstance()->execute("INSERT INTO `tl_isotope_stock_jvh_booking_event` (`booking_id`, `tstamp`) VALUES " . implode(", ", $insertIds));
      }
      $url = $this->generateUrl('contao_backend', ['do' => 'tl_isotope_stock_booking']);
      return new RedirectResponse($url);
    }
    if ($form->isSubmitted() && !$form->isValid()) {
      $response->setStatusCode(422);
    }

    $templateData = [
      'title' => $GLOBALS['TL_LANG']['tl_isotope_stock_booking']['new_mass_booking'],
      'form' => $form->createView(),
      'product_sku_url' => $this->generateUrl('tl_isotope_stock_booking_product_sku'),
    ];
    $content = $this->twig->render('@JvHIsotopeStock/tl_isotope_stock_booking_mass_booking.html.twig', $templateData);
    $response->setContent($content);
    return $response;
  }
}
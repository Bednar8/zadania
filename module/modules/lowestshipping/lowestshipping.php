<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class LowestShipping extends Module
{
    public function __construct()
    {
        $this->name = 'lowestshipping';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Paweł Bednarski';
        $this->need_instance = false;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Lowest Shipping');
        $this->description = $this->l('Show lowest shipping cost for product on product page');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayProductAdditionalInfo');
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        $cart = $this->context->cart;
        $has_cart = false;
        $lowest_delivery_price = null;  

        if (!$cart || !$cart->nbProducts()) {
            $has_cart = false;
        } else {
            $has_cart = true;
        }

        if ($has_cart) {
            $default_country = $this->context->country ?? null;
            $delivery_options = $cart->getDeliveryOptionList($default_country);

            if (empty($delivery_options)) {
                $id_zone = null;
                
                if ($cart->id_address_delivery) {
                    $address = new Address($cart->id_address_delivery);
                    if ($address->id_country) {
                        $id_zone = Country::getIdZone($address->id_country);
                    }
                }

                if (!$id_zone && $this->context->country) {
                    $id_zone = Country::getIdZone($this->context->country->id);
                }
                
                if ($id_zone) {
                    $carriers = Carrier::getCarriersForOrder($id_zone, null, $cart);
                    
                    foreach ($carriers as $carrier) {
                        $shipping_cost = $cart->getPackageShippingCost($carrier['id_carrier'], true);
                        if ($shipping_cost !== false && ($lowest_delivery_price === null || $shipping_cost < $lowest_delivery_price)) {
                            $lowest_delivery_price = $shipping_cost;
                        }
                    }
                }
            } else {
                foreach ($delivery_options as $address_id => $address_options) {
                    foreach ($address_options as $option_key => $option) {
                        if (!isset($option['carrier_list'])) {
                            continue;
                        }

                        foreach ($option['carrier_list'] as $carrier_id => $carrier_info) {
                            $price = null;
                            if (isset($carrier_info['price_tax_incl'])) {
                                $price = (float)$carrier_info['price_tax_incl'];
                            } elseif (isset($carrier_info['price'])) {
                                $price = (float)$carrier_info['price'];
                            } elseif (isset($carrier_info['total_price_tax_incl'])) {
                                $price = (float)$carrier_info['total_price_tax_incl'];
                            } elseif (isset($carrier_info['total_price'])) {
                                $price = (float)$carrier_info['total_price'];
                            }
                            
                            if ($price !== null && ($lowest_delivery_price === null || $price < $lowest_delivery_price)) {
                                $lowest_delivery_price = $price;
                            }
                        }
                    }
                }
                
                if ($lowest_delivery_price === null) {
                    $id_zone = null;
                    
                    if ($cart->id_address_delivery) {
                        $address = new Address($cart->id_address_delivery);
                        if ($address->id_country) {
                            $id_zone = Country::getIdZone($address->id_country);
                        }
                    }
                    
                    if (!$id_zone && $this->context->country) {
                        $id_zone = Country::getIdZone($this->context->country->id);
                    }
                    
                    if ($id_zone) {
                        $carriers = Carrier::getCarriersForOrder($id_zone, null, $cart);
                        
                        foreach ($carriers as $carrier) {
                            $shipping_cost = $cart->getPackageShippingCost($carrier['id_carrier'], true);
                            if ($shipping_cost !== false && ($lowest_delivery_price === null || $shipping_cost < $lowest_delivery_price)) {
                                $lowest_delivery_price = $shipping_cost;
                            }
                        }
                    }
                }
            }
        }

        $formatted_price = null;
        if ($lowest_delivery_price !== null && isset($this->context->currency)) {
            $currency = $this->context->currency;
            $price_formatted = number_format($lowest_delivery_price, 2, '.', '');
            if ($currency->format == 1) {
                $formatted_price = $currency->sign . ' ' . $price_formatted;
            } else {
                $formatted_price = $price_formatted . ' ' . $currency->sign;
            }
        }
        
        $this->context->smarty->assign([
            'has_cart' => $has_cart,
            'lowest_delivery_price' => $formatted_price,
        ]);

        return $this->display(__FILE__, 'lowestshipping.tpl');
    }
}

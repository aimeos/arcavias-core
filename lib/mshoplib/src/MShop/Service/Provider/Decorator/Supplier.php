<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package MShop
 * @subpackage Service
 */


namespace Aimeos\MShop\Service\Provider\Decorator;


/**
 * Supplier address decorator for service providers
 *
 * @package MShop
 * @subpackage Service
 */
class Supplier
	extends \Aimeos\MShop\Service\Provider\Decorator\Base
	implements \Aimeos\MShop\Service\Provider\Decorator\Iface
{
	private $feConfig = array(
		'supplier.code' => array(
			'code' => 'supplier.code',
			'internalcode' => 'supplier.code',
			'label' => 'Pick-up address',
			'type' => 'list',
			'internaltype' => 'array',
			'default' => [],
			'required' => true
		),
	);
	
	private $beConfig = array(
		'supplier.display-code' => array(
			'code' => 'supplier.display-code',
			'internalcode' => 'supplier.display-code',
			'label' => 'Display supplier code in summary and e-mail',
			'type' => 'boolean',
			'internaltype' => 'boolean',
			'default' => 1,
			'required' => false,
		),
		'supplier.display-name' => array(
			'code' => 'supplier.display-name',
			'internalcode' => 'supplier.display-name',
			'label' => 'Display supplier company name or label in summary and e-mail',
			'type' => 'boolean',
			'internaltype' => 'boolean',
			'default' => 0,
			'required' => false,
		),
		'supplier.display-address' => array(
			'code' => 'supplier.display-address',
			'internalcode' => 'supplier.display-address',
			'label' => 'Display supplier address in summary and e-mail',
			'type' => 'boolean',
			'internaltype' => 'boolean',
			'default' => 0,
			'required' => false,
		),
	);


	/**
	 * Initializes a new service provider object using the given context object.
	 *
	 * @param \Aimeos\MShop\Service\Provider\Iface $provider Service provider or decorator
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object with required objects
	 * @param \Aimeos\MShop\Service\Item\Iface $serviceItem Service item with configuration for the provider
	 */
	public function __construct( \Aimeos\MShop\Service\Provider\Iface $provider,
		\Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MShop\Service\Item\Iface $serviceItem )
	{
		parent::__construct( $provider, $context, $serviceItem );

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'supplier' );
		$addrManager = \Aimeos\MShop\Factory::createManager( $context, 'supplier/address' );

		$search = $manager->createSearch( true );
		$search->setSortations( [$search->sort( '+', 'supplier.label' )] );

		foreach( $manager->searchItems( $search, ['supplier/address'] ) as $item )
		{
			$addresses = $item->getAddressItems();

			if( empty( $addresses ) ) {
				$addresses[] = $addrManager->createItem();
			}

			foreach( $addresses as $id => $addr ) {
				$addrId = ( count($addresses) > 1) ? $item->getCode().'-'.$id : $item->getCode();
			
				$this->feConfig['supplier.code']['default'][$addrId] = preg_replace( "/\n+/m", "\n", sprintf(

				/// Supplier address format with label (%1$s), company (%2$s),
				/// address part one (%3$s, e.g street), address part two (%4$s, e.g house number), address part three (%5$s, e.g additional information),
				/// postal/zip code (%6$s), city (%7$s), state (%8$s), country ID (%9$s),
				/// e-mail (%10$s), phone (%11$s), facsimile/telefax (%12$s), web site (%13$s)
					$context->getI18n()->dt( 'mshop', '%1$s
%2$s
%3$s %4$s
%5$s
%6$s %7$s
%8$s %9$s
%10$s
%11$s
%12$s
%13$s
' 					),
					$item->getLabel(),
					$addr->getCompany(),
					$addr->getAddress1(),
					$addr->getAddress2(),
					$addr->getAddress3(),
					$addr->getPostal(),
					$addr->getCity(),
					$addr->getState(),
					$addr->getCountryId(),
					$addr->getEmail(),
					$addr->getTelephone(),
					$addr->getTelefax(),
					$addr->getWebsite()
				) );
				
				if( !empty($addr->getAddress1()) && !empty($addr->getCity()) ) {
					$this->feConfig['supplier.code']['short'][$addrId] = preg_replace( "/\n+/m", "\n", sprintf(

					/// Supplier address format with address part one (%1$s, e.g street), address part two (%2$s, e.g house number),
					/// postal/zip code (%3$s), city (%4$s)
						$context->getI18n()->dt( 'mshop', '%1$s %2$s, %3$s %4$s'),
						$addr->getAddress1(),
						$addr->getAddress2(),
						$addr->getPostal(),
						$addr->getCity()
					) );
				}
				
				if( !empty($addr->getCompany()) || !empty($item->getLabel()) ) {
					$this->feConfig['supplier.code']['name'][$addrId] = !empty($addr->getCompany()) ? $addr->getCompany() : $item->getLabel();
				}
			}
		}
	}


	/**
	 * Checks the frontend configuration attributes for validity.
	 *
	 * @param array $attributes Attributes entered by the customer during the checkout process
	 * @return array An array with the attribute keys as key and an error message as values for all attributes that are
	 * 	known by the provider but aren't valid resp. null for attributes whose values are OK
	 */
	public function checkConfigFE( array $attributes )
	{
		$result = $this->getProvider()->checkConfigFE( $attributes );

		return array_merge( $result, $this->checkConfig( $this->feConfig, $attributes ) );
	}


	/**
	 * Returns the configuration attribute definitions of the provider to generate a list of available fields and
	 * rules for the value of each field in the frontend.
	 *
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Basket object
	 * @return array List of attribute definitions implementing \Aimeos\MW\Common\Critera\Attribute\Iface
	 */
	public function getConfigFE( \Aimeos\MShop\Order\Item\Base\Iface $basket )
	{
		$feconfig = $this->feConfig;

		try
		{
			$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_DELIVERY;
			$service = $basket->getService( $type, $this->getServiceItem()->getCode() );

			if( ( $value = $service->getAttribute( 'supplier.code', 'delivery' ) ) != ''
				&& isset( $feconfig['supplier.code']['default'][$value] )
			) {
				// move to first position so it's selected
				$address = $feconfig['supplier.code']['default'][$value];
				unset( $feconfig['supplier.code']['default'][$value] );
				$feconfig['supplier.code']['default'] = [$value => $address] + $feconfig['supplier.code']['default'];
			}
		}
		catch( \Aimeos\MShop\Order\Exception $e ) {} // If service isn't available

		return array_merge( $this->getProvider()->getConfigFE( $basket ), $this->getConfigItems( $feconfig ) );
	}


	/**
	 * Sets the delivery attributes in the given service.
	 *
	 * @param \Aimeos\MShop\Order\Item\Base\Service\Iface $orderServiceItem Order service item that will be added to the basket
	 * @param array $attributes Attribute key/value pairs entered by the customer during the checkout process
	 */
	public function setConfigFE( \Aimeos\MShop\Order\Item\Base\Service\Iface $orderServiceItem, array $attributes ) {
		if( ( $code = $attributes['supplier.code'] ) != '' ) {
			
			if( $this->getConfigValue('supplier.display-name')
				&& isset( $this->feConfig['supplier.code']['name'][$code] ) 
			) {
				// add name as attribute for summary page / customer email
				$attributes['supplier.name'] = $this->feConfig['supplier.code']['name'][$code];
			}
			
			if( $this->getConfigValue('supplier.display-address') 
				&& isset( $this->feConfig['supplier.code']['short'][$code] ) 
			) {
				// add short address as attribute for summary page / customer email
				$attributes['supplier.address'] = $this->feConfig['supplier.code']['short'][$code];
			}
			
			if( $this->getConfigValue('supplier.display-code') === 0 ) {
				// remove code attribute for summary page / customer email
				unset( $attributes['supplier.code'] );
			}
			
		}

		$this->setAttributes( $orderServiceItem, $attributes, 'delivery' );
	}
}

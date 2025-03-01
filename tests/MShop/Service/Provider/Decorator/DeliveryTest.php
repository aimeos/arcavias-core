<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2025
 */


namespace Aimeos\MShop\Service\Provider\Decorator;


class DeliveryTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $basket;
	private $context;
	private $servItem;
	private $mockProvider;


	protected function setUp() : void
	{
		$this->context = \TestHelper::context();

		$servManager = \Aimeos\MShop::create( $this->context, 'service' );
		$this->servItem = $servManager->create()->setCode( 'unitdeliverycode' )->setConfig( ['delivery.partial' => 1] );

		$this->mockProvider = $this->getMockBuilder( \Aimeos\MShop\Service\Provider\Decorator\Date::class )
			->disableOriginalConstructor()->getMock();

		$this->basket = \Aimeos\MShop::create( $this->context, 'order' )->create();

		$this->object = new \Aimeos\MShop\Service\Provider\Decorator\Delivery( $this->mockProvider, $this->context, $this->servItem );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->basket, $this->mockProvider, $this->servItem, $this->context );
	}


	public function testGetConfigBE()
	{
		$this->mockProvider->expects( $this->once() )
			->method( 'getConfigBE' )
			->willReturn( [] );

		$result = $this->object->getConfigBE();

		$this->assertEquals( 2, count( $result ) );
		$this->assertArrayHasKey( 'delivery.partial', $result );
		$this->assertArrayHasKey( 'delivery.collective', $result );
	}


	public function testCheckConfigBE()
	{
		$this->mockProvider->expects( $this->once() )
			->method( 'checkConfigBE' )
			->willReturn( [] );

		$attributes = array(
			'delivery.partial' => '0',
			'delivery.collective' => '0',
		);
		$result = $this->object->checkConfigBE( $attributes );

		$this->assertEquals( 2, count( $result ) );
		$this->assertNull( $result['delivery.partial'] );
		$this->assertNull( $result['delivery.collective'] );
	}


	public function testCheckConfigBENoConfig()
	{
		$this->mockProvider->expects( $this->once() )
			->method( 'checkConfigBE' )
			->willReturn( [] );

		$result = $this->object->checkConfigBE( [] );

		$this->assertEquals( 2, count( $result ) );
		$this->assertNull( $result['delivery.partial'] );
		$this->assertNull( $result['delivery.collective'] );
	}


	public function testCheckConfigBEFailure()
	{
		$this->mockProvider->expects( $this->once() )
			->method( 'checkConfigBE' )
			->willReturn( [] );

		$attributes = array(
			'delivery.partial' => [],
			'delivery.collective' => [],
		);
		$result = $this->object->checkConfigBE( $attributes );

		$this->assertEquals( 2, count( $result ) );
		$this->assertIsString( $result['delivery.partial'] );
		$this->assertIsString( $result['delivery.collective'] );
	}


	public function testGetConfigFE()
	{
		$orderManager = \Aimeos\MShop::create( \TestHelper::context(), 'order' );
		$search = $orderManager->filter();
		$expr = array(
			$search->compare( '==', 'order.channel', 'web' ),
			$search->compare( '==', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED )
		);
		$search->setConditions( $search->and( $expr ) );
		$orderItems = $orderManager->search( $search )->toArray();

		if( ( $order = reset( $orderItems ) ) === false ) {
			throw new \RuntimeException( sprintf( 'No Order found with statuspayment "%1$s" and type "%2$s"', \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED, 'web' ) );
		}


		$this->mockProvider->expects( $this->once() )->method( 'getConfigFE' )->willReturn( [] );

		$basket = $orderManager->get( $order->getId(), ['order/service'] );
		$config = $this->object->getConfigFE( $basket );

		$this->assertIsArray( $config['delivery.type']->getDefault() );
	}


	public function testCheckConfigFE()
	{
		$this->mockProvider->expects( $this->once() )
			->method( 'checkConfigFE' )
			->willReturn( [] );

		$result = $this->object->checkConfigFE( ['delivery.type' => '0'] );

		$this->assertEquals( ['delivery.type' => null], $result );
	}


	public function testCheckConfigFEwrongType()
	{
		$this->mockProvider->expects( $this->once() )
			->method( 'checkConfigFE' )
			->willReturn( [] );

		$result = $this->object->checkConfigFE( ['delivery.type' => null] );

		$this->assertArrayHasKey( 'delivery.type', $result );
		$this->assertFalse( $result['delivery.type'] === null );
	}


	public function testCheckConfigFEwrongValue()
	{
		$this->mockProvider->expects( $this->once() )
			->method( 'checkConfigFE' )
			->willReturn( [] );

		$result = $this->object->checkConfigFE( ['delivery.type' => 2] );

		$this->assertArrayHasKey( 'delivery.type', $result );
		$this->assertFalse( $result['delivery.type'] === null );
	}
}

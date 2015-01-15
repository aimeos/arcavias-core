<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2014
 * @license LGPLv3, http://www.arcavias.com/en/license
 * @package Client
 * @subpackage Html
 */


/**
 * Default implementation of account watch HTML client.
 *
 * @package Client
 * @subpackage Html
 */
class Client_Html_Account_Watch_Default
	extends Client_Html_Abstract
{
	/** client/html/account/watch/default/subparts
	 * List of HTML sub-clients rendered within the account watch section
	 *
	 * The output of the frontend is composed of the code generated by the HTML
	 * clients. Each HTML client can consist of serveral (or none) sub-clients
	 * that are responsible for rendering certain sub-parts of the output. The
	 * sub-clients can contain HTML clients themselves and therefore a
	 * hierarchical tree of HTML clients is composed. Each HTML client creates
	 * the output that is placed inside the container of its parent.
	 *
	 * At first, always the HTML code generated by the parent is printed, then
	 * the HTML code of its sub-clients. The order of the HTML sub-clients
	 * determines the order of the output of these sub-clients inside the parent
	 * container. If the configured list of clients is
	 *
	 *  array( "subclient1", "subclient2" )
	 *
	 * you can easily change the order of the output by reordering the subparts:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1", "subclient2" )
	 *
	 * You can also remove one or more parts if they shouldn't be rendered:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1" )
	 *
	 * As the clients only generates structural HTML, the layout defined via CSS
	 * should support adding, removing or reordering content by a fluid like
	 * design.
	 *
	 * @param array List of sub-client names
	 * @since 2014.03
	 * @category Developer
	 */
	private $_subPartPath = 'client/html/account/watch/default/subparts';
	private $_subPartNames = array();
	private $_typeItem;
	private $_cache;


	/**
	 * Returns the HTML code for insertion into the body.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return string HTML code
	 */
	public function getBody( $uid = '', array &$tags = array(), &$expire = null )
	{
		$context = $this->_getContext();
		$view = $this->getView();

		try
		{
			$view = $this->_setViewParams( $view, $tags, $expire );

			$html = '';
			foreach( $this->_getSubClients() as $subclient ) {
				$html .= $subclient->setView( $view )->getBody( $uid, $tags, $expire );
			}
			$view->watchBody = $html;
		}
		catch( Client_Html_Exception $e )
		{
			$error = array( $this->_getContext()->getI18n()->dt( 'client/html', $e->getMessage() ) );
			$view->watchErrorList = $view->get( 'watchErrorList', array() ) + $error;
		}
		catch( Controller_Frontend_Exception $e )
		{
			$error = array( $this->_getContext()->getI18n()->dt( 'controller/frontend', $e->getMessage() ) );
			$view->watchErrorList = $view->get( 'watchErrorList', array() ) + $error;
		}
		catch( MShop_Exception $e )
		{
			$error = array( $this->_getContext()->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->watchErrorList = $view->get( 'watchErrorList', array() ) + $error;
		}
		catch( Exception $e )
		{
			$context->getLogger()->log( $e->getMessage() . PHP_EOL . $e->getTraceAsString() );

			$error = array( $context->getI18n()->dt( 'client/html', 'A non-recoverable error occured' ) );
			$view->watchErrorList = $view->get( 'watchErrorList', array() ) + $error;
		}

		/** client/html/account/watch/default/template-body
		 * Relative path to the HTML body template of the account watch client.
		 *
		 * The template file contains the HTML code and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the layouts directory (usually in client/html/layouts).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "default" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "default"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating code for the HTML page body
		 * @since 2014.03
		 * @category Developer
		 * @see client/html/account/watch/default/template-header
		 */
		$tplconf = 'client/html/account/watch/default/template-body';
		$default = 'account/watch/body-default.html';

		return $view->render( $this->_getTemplate( $tplconf, $default ) );
	}


	/**
	 * Returns the HTML string for insertion into the header.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return string String including HTML tags for the header
	 */
	public function getHeader( $uid = '', array &$tags = array(), &$expire = null )
	{
		try
		{
			$view = $this->_setViewParams( $this->getView(), $tags, $expire );

			$html = '';
			foreach( $this->_getSubClients() as $subclient ) {
				$html .= $subclient->setView( $view )->getHeader( $uid, $tags, $expire );
			}
			$view->watchHeader = $html;

			/** client/html/account/watch/default/template-header
			 * Relative path to the HTML header template of the account watch client.
			 *
			 * The template file contains the HTML code and processing instructions
			 * to generate the HTML code that is inserted into the HTML page header
			 * of the rendered page in the frontend. The configuration string is the
			 * path to the template file relative to the layouts directory (usually
			 * in client/html/layouts).
			 *
			 * You can overwrite the template file configuration in extensions and
			 * provide alternative templates. These alternative templates should be
			 * named like the default one but with the string "default" replaced by
			 * an unique name. You may use the name of your project for this. If
			 * you've implemented an alternative client class as well, "default"
			 * should be replaced by the name of the new class.
			 *
			 * @param string Relative path to the template creating code for the HTML page head
			 * @since 2014.03
			 * @category Developer
			 * @see client/html/account/watch/default/template-body
			 */
			$tplconf = 'client/html/account/watch/default/template-header';
			$default = 'account/watch/header-default.html';

			return $view->render( $this->_getTemplate( $tplconf, $default ) );
		}
		catch( Exception $e )
		{
			$this->_getContext()->getLogger()->log( $e->getMessage() . PHP_EOL . $e->getTraceAsString() );
		}
	}


	/**
	 * Returns the sub-client given by its name.
	 *
	 * @param string $type Name of the client type
	 * @param string|null $name Name of the sub-client (Default if null)
	 * @return Client_Html_Interface Sub-client object
	 */
	public function getSubClient( $type, $name = null )
	{
		return $this->_createSubClient( 'account/watch/' . $type, $name );
	}


	/**
	 * Processes the input, e.g. store given values.
	 * A view must be available and this method doesn't generate any output
	 * besides setting view variables.
	 */
	public function process()
	{
		$view = $this->getView();
		$context = $this->_getContext();
		$ids = $view->param( 'watch_id', array() );

		if( $context->getUserId() != null && !empty( $ids ) )
		{
			$typeItem = $this->_getTypeItem( 'customer/list/type', 'product', 'watch' );
			$manager = MShop_Factory::createManager( $context, 'customer/list' );

			$search = $manager->createSearch();
			$expr = array(
				$search->compare( '==', 'customer.list.parentid', $context->getUserId() ),
				$search->compare( '==', 'customer.list.refid', $ids ),
				$search->compare( '==', 'customer.list.domain', 'product' ),
				$search->compare( '==', 'customer.list.typeid', $typeItem->getId() ),
			);
			$search->setConditions( $search->combine( '&&', $expr ) );

			$items = array();
			foreach( $manager->searchItems( $search ) as $item ) {
				$items[ $item->getRefId() ] = $item;
			}


			switch( $view->param( 'watch_action' ) )
			{
				case 'add':

					$search = $manager->createSearch();
					$expr = array(
						$search->compare( '==', 'customer.list.parentid', $context->getUserId() ),
						$search->compare( '==', 'customer.list.typeid', $typeItem->getId() ),
						$search->compare( '==', 'customer.list.domain', 'product' ),
					);
					$search->setConditions( $search->combine( '&&', $expr ) );
					$search->setSlice( 0, 0 );

					$total = 0;
					$manager->searchItems( $search, array(), $total );

					/** client/html/account/watch/default/maxitems
					 * Maximum number of products that can be watched in parallel
					 *
					 * This option limits the number of products that can be watched
					 * after the users added the products to their watch list.
					 * It must be a positive integer value greater than 0.
					 *
					 * Note: It's recommended to set this value not too high as this
					 * leads to a high memory consumption when the e-mails are generated
					 * to notify the customers. The memory used will up to 100*maxitems
					 * of the footprint of one product item including the associated
					 * texts, prices and media.
					 *
					 * @param integer Number of products
					 * @since 2014.09
					 * @category User
					 * @category Developer
					 */
					$max = $context->getConfig()->get( 'client/html/account/watch/default/maxitems', 100 );

					$item = $manager->createItem();
					$item->setParentId( $context->getUserId() );
					$item->setTypeId( $typeItem->getId() );
					$item->setDomain( 'product' );
					$item->setStatus( 1 );

					foreach( (array) $view->param( 'watch_id', array() ) as $id )
					{
						if( $total >= $max )
						{
							$error = array( sprintf( $context->getI18n()->dt( 'client/html', 'You can only watch up to %1$s products' ), $max ) );
							$view->watchErrorList = $view->get( 'watchErrorList', array() ) + $error;
							break;
						}

						if( !isset( $items[$id] ) )
						{
							$item->setId( null );
							$item->setRefId( $id );

							$manager->saveItem( $item );
							$manager->moveItem( $item->getId() );

							$total++;
						}
					}

					break;

				case 'edit':

					foreach( (array) $view->param( 'watch_id', array() ) as $id )
					{
						if( isset( $items[$id] ) )
						{
							$item = $items[$id];

							$config = array(
								'timeframe' => $view->param( 'watch_timeframe', 7 ),
								'pricevalue' => $view->param( 'watch_pricevalue', '0.00' ),
								'price' => $view->param( 'watch_price', 0 ),
								'stock' => $view->param( 'watch_stock', 0 ),
							);
							$time = time() + ( $config['timeframe'] + 1 ) * 86400;

							$item->setDateEnd( date( 'Y-m-d 00:00:00', $time ) );
							$item->setConfig( $config );

							$manager->saveItem( $item );
						}
					}

					break;

				case 'delete':

					$listIds = array();

					foreach( (array) $view->param( 'watch_id', array() ) as $id )
					{
						if( isset( $items[$id] ) ) {
							$listIds[] = $items[$id]->getId();
						}
					}

					$manager->deleteItems( $listIds );
					break;
			}
		}

		parent::process();
	}


	/**
	 * Returns the list of sub-client names configured for the client.
	 *
	 * @return array List of HTML client names
	 */
	protected function _getSubClientNames()
	{
		return $this->_getContext()->getConfig()->get( $this->_subPartPath, $this->_subPartNames );
	}


	/**
	 * Returns the sanitized page from the parameters for the product list.
	 *
	 * @param MW_View_Interface $view View instance with helper for retrieving the required parameters
	 * @return integer Page number starting from 1
	 */
	protected function _getProductListPage( MW_View_Interface $view )
	{
		$page = (int) $view->param( 'watch_page', 1 );
		return ( $page < 1 ? 1 : $page );
	}


	/**
	 * Returns the sanitized page size from the parameters for the product list.
	 *
	 * @param MW_View_Interface $view View instance with helper for retrieving the required parameters
	 * @return integer Page size
	 */
	protected function _getProductListSize( MW_View_Interface $view )
	{
		/** client/html/account/watch/size
		 * The number of products shown in a list page for watch products
		 *
		 * Limits the number of products that is shown in the list pages to the
		 * given value. If more products are available, the products are split
		 * into bunches which will be shown on their own list page. The user is
		 * able to move to the next page (or previous one if it's not the first)
		 * to display the next (or previous) products.
		 *
		 * The value must be an integer number from 1 to 100. Negative values as
		 * well as values above 100 are not allowed. The value can be overwritten
		 * per request if the "l-size" parameter is part of the URL.
		 *
		 * @param integer Number of products
		 * @since 2014.09
		 * @category User
		 * @category Developer
		 * @see client/html/catalog/list/size
		 */
		$defaultSize = $this->_getContext()->getConfig()->get( 'client/html/account/watch/size', 48 );

		$size = (int) $view->param( 'watch-size', $defaultSize );
		return ( $size < 1 || $size > 100 ? $defaultSize : $size );
	}


	/**
	 * Sets the necessary parameter values in the view.
	 *
	 * @param MW_View_Interface $view The view object which generates the HTML output
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return MW_View_Interface Modified view object
	 */
	protected function _setViewParams( MW_View_Interface $view, array &$tags = array(), &$expire = null )
	{
		if( !isset( $this->_cache ) )
		{
			$total = 0;
			$productIds = array();
			$context = $this->_getContext();
			$typeItem = $this->_getTypeItem( 'customer/list/type', 'product', 'watch' );

			$size = $this->_getProductListSize( $view );
			$current = $this->_getProductListPage( $view );
			$last = ( $total != 0 ? ceil( $total / $size ) : 1 );


			$manager = MShop_Factory::createManager( $context, 'customer/list' );

			$search = $manager->createSearch();
			$expr = array(
				$search->compare( '==', 'customer.list.parentid', $context->getUserId() ),
				$search->compare( '==', 'customer.list.typeid', $typeItem->getId() ),
				$search->compare( '==', 'customer.list.domain', 'product' ),
			);
			$search->setConditions( $search->combine( '&&', $expr ) );
			$search->setSortations( array( $search->sort( '-', 'customer.list.position' ) ) );
			$search->setSlice( ($current-1) * $size, $size );

			$view->watchListItems = $manager->searchItems( $search, array(), $total );


			/** client/html/account/watch/domains
			 * A list of domain names whose items should be available in the account watch view template
			 *
			 * The templates rendering product details usually add the images,
			 * prices and texts associated to the product item. If you want to
			 * display additional or less content, you can configure your own
			 * list of domains (attribute, media, price, product, text, etc. are
			 * domains) whose items are fetched from the storage. Please keep
			 * in mind that the more domains you add to the configuration, the
			 * more time is required for fetching the content!
			 *
			 * @param array List of domain names
			 * @since 2014.09
			 * @category Developer
			 * @see client/html/catalog/domains
			 */
			$default = array( 'text', 'price', 'media' );
			$domains = $context->getConfig()->get( 'client/html/account/watch/domains', $default );

			foreach( $view->watchListItems as $listItem ) {
				$productIds[] = $listItem->getRefId();
			}

			$manager = MShop_Factory::createManager( $context, 'product' );

			$search = $manager->createSearch();
			$search->setConditions( $search->compare( '==', 'product.id', $productIds ) );
			$search->setSlice( 0, count( $view->watchListItems ) );

			$view->watchProductItems = $manager->searchItems( $search, $domains );


			$view->watchPageFirst = 1;
			$view->watchPagePrev = ( $current > 1 ? $current - 1 : 1 );
			$view->watchPageNext = ( $current < $last ? $current + 1 : $last );
			$view->watchPageLast = $last;
			$view->watchPageCurr = $current;

			$this->_cache = $view;
		}

		return $this->_cache;
	}
}
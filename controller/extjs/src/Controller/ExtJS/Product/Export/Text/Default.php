<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2013
 * @license LGPLv3, http://www.arcavias.com/en/license
 * @package Controller
 * @subpackage ExtJS
 */



/**
 * ExtJS product text export controller for admin interfaces.
 *
 * @package Controller
 * @subpackage ExtJS
 */
class Controller_ExtJS_Product_Export_Text_Default
	extends Controller_ExtJS_Common_Load_Text_Abstract
	implements Controller_ExtJS_Common_Load_Text_Interface
{
	/**
	 * Initializes the controller.
	 *
	 * @param MShop_Context_Item_Interface $context MShop context object
	 */
	public function __construct( MShop_Context_Item_Interface $context )
	{
		parent::__construct( $context, 'Product_Export_Text' );
	}


	/**
	 * Creates a new job to export an zip file.
	 *
	 * @param stdClass $params Object containing the properties, e.g. the list of product IDs
	 */
	public function createJob( stdClass $params )
	{
		$this->_checkParams( $params, array( 'site', 'items' ) );
		$this->_setLocale( $params->site );

		$config = $this->_getContext()->getConfig();
		$dir = $config->get( 'controller/extjs/product/export/text/default/exportdir', 'uploads' );

		$items = (array) $params->items;
		$lang = ( property_exists( $params, 'lang' ) ) ? (array) $params->lang : array();

		$languages = ( !empty( $lang ) ) ? implode( $lang, '-' ) : 'all';

		$result = (object) array(
			'site' => $params->site,
			'items' => array(
				(object) array(
					'job.label' => 'Product text export: '. $languages,
					'job.method' => 'Product_Export_Text.exportFile',
					'job.parameter' => array(
						'site' => $params->site,
						'items' => $items,
						'lang' => $params->lang,
					),
					'job.status' => 1,
				),
			),
		);

		$jobController = Controller_ExtJS_Admin_Job_Factory::createController( $this->_getContext() );
		$jobController->saveItems( $result );

		return array(
			'items' => $items,
			'success' => true,
		);
	}


	/**
	 * Create csv files in zip in the filesystem.
	 *
	 * @param stdClass $params Object containing the properties, e.g. the list of product IDs
	 */
	public function exportFile( stdClass $params )
	{
		$this->_checkParams( $params, array( 'site', 'items' ) );
		$this->_setLocale( $params->site );
		$actualLangid = $this->_getContext()->getLocale()->getLanguageId();

		$items = (array) $params->items;
		$lang = ( property_exists( $params, 'lang' ) ) ? (array) $params->lang : array();

		$config = $this->_getContext()->getConfig();
		$dir = $config->get( 'controller/extjs/product/export/text/default/exportdir', 'uploads' );
		$perms = $config->get( 'controller/extjs/product/export/text/default/dirperms', 0775 );

		$foldername = 'product-text-export_' . date('Y-m-d') . '_' . md5( time() . getmypid() );
		$tmpfolder = $dir . DIRECTORY_SEPARATOR . $foldername;

		if( is_dir( $dir ) === false && mkdir( $dir, $perms, true ) === false ) {
			throw new Controller_ExtJS_Exception( sprintf( 'Couldn\'t create directory "%1$s" with permissions "%2$o"', $dir, $perms ) );
		}

		if( mkdir( $tmpfolder, $perms, true ) === false ) {
			throw new Controller_ExtJS_Exception( sprintf( 'Couldn\'t create directory "%1$s" with permissions "%2$o"', $tmpfolder, $perms ) );
		}

		$this->_getContext()->getLogger()->log( sprintf( 'Create export directory for product IDs: %1$s', implode( ',', $items ) ), MW_Logger_Abstract::DEBUG );

		try
		{
			$this->_getContext()->getLocale()->setLanguageId( $actualLangid );

			$filename = $this->_exportProductData( $items, $lang, $tmpfolder . '.zip', '.csv' );
		}
		catch ( Exception $e )
		{
			$this->_removeTempFiles( $tmpfolder );
			throw $e;
		}

		$this->_removeTempFiles( $tmpfolder );

		return array(
			'file' => '<a href="'.$filename.'">Download</a>',
		);
	}


	/**
	 * Returns the service description of the class.
	 * It describes the class methods and its parameters including their types
	 *
	 * @return array Associative list of class/method names, their parameters and types
	 */
	public function getServiceDescription()
	{
		return array(
			'Product_Export_Text.createHttpOutput' => array(
				"parameters" => array(
					array( "type" => "string","name" => "site","optional" => false ),
					array( "type" => "array","name" => "items","optional" => false ),
					array( "type" => "array","name" => "lang","optional" => true ),
				),
				"returns" => "",
			),
		);
	}


	/**
	 * Exports data to csv files.
	 *
	 * @param array $ids List of item IDs that should be part of the document
	 * @param array $lang List of languages to export (empty array for all)
	 * @param string $tmpfolder Temporary folder name where to write export files
	 * @return string Path of export file
	 */
	protected function _exportProductData( array $ids, array $lang, $filename, $contentFormat = '' )
	{
		$manager = MShop_Locale_Manager_Factory::createManager( $this->_getContext() );
		$globalLanguageManager = $manager->getSubManager( 'language' );

		$search = $globalLanguageManager->createSearch();
		$search->setSortations( array( $search->sort( '+', 'locale.language.id') ) );

		if( !empty( $lang ) ) {
			$search->setConditions( $search->compare( '==', 'locale.language.id', $lang ) );
		}

		$containerItem = $this->_initContainer( $filename );

		$start = 0;

		do
		{
			$result = $globalLanguageManager->searchItems( $search );

			foreach ( $result as $item )
			{
				$langid = $item->getId();

				$contentItem = $containerItem->create( $langid . $contentFormat );
				$contentItem->add( array( 'Language ID', 'Product type', 'Product code', 'List type', 'Text type', 'Text ID', 'Text' ) );
				$this->_getContext()->getLocale()->setLanguageId( $langid );
				$this->_addLanguage( $langid, $ids, $contentItem );

				$containerItem->add( $contentItem );
			}

			$count = count( $result );
			$start += $count;
			$search->setSlice( $start );
		}
		while( $count == $search->getSliceSize() );

		$containerItem->close();

		return $filename;
	}


	/**
	 * Adds data for the given language.
	 *
	 * @param string $langid Language id
	 * @param array $items List of of item ids whose texts should be added
	 * @param MW_Container_Content_Interface $contentItem Content item
	 */
	protected function _addLanguage( $langid, array $ids, MW_Container_Content_Interface $contentItem )
	{
		$manager = MShop_Product_Manager_Factory::createManager( $this->_getContext() );
		$search = $manager->createSearch();

		if( !empty( $ids ) ) {
			$search->setConditions( $search->compare( '==', 'product.id', $ids ) );
		}

		$sort = array( $search->sort( '+', 'product.type.code' ), $search->sort( '+', 'product.code' ) );
		$search->setSortations( $sort );

		$start = 0;

		do
		{
			$result = $manager->searchItems( $search, array('text') );

			foreach( $result as $item ) {
				$this->_addItem( $langid, $item, $contentItem );
			}

			$count = count( $result );
			$start += $count;
			$search->setSlice( $start );
		}
		while( $count == $search->getSliceSize() );
	}


	/**
	 * Adds all texts belonging to an product item.
	 *
	 * @param string $langid Language id
	 * @param MShop_Product_Item_Interface $item product item object
	 * @param MW_Container_Content_Interface $contentItem Content item
	 */
	protected function _addItem( $langid, MShop_Product_Item_Interface $item, MW_Container_Content_Interface $contentItem )
	{
		$listTypes = $items = array();
		foreach( $item->getListItems( 'text' ) as $listItem ) {
			$listTypes[ $listItem->getRefId() ] = $listItem->getType();
		}

		foreach( $this->_getTextTypes( 'product' ) as $textTypeItem )
		{
			$textItems = $item->getRefItems( 'text', $textTypeItem->getCode() );

			if( !empty( $textItems ) )
			{
				foreach( $textItems as $textItem )
				{
					$listType = ( isset( $listTypes[ $textItem->getId() ] ) ? $listTypes[ $textItem->getId() ] : '' );

					$items = array( $langid, $item->getType(), $item->getCode(), $listType, $textTypeItem->getCode(), '', '' );

					// use language of the text item because it may be null
					if( ( $textItem->getLanguageId() == $langid || is_null( $textItem->getLanguageId() ) )
						&& $textItem->getTypeId() == $textTypeItem->getId() )
					{
						$items[0] = $textItem->getLanguageId();
						$items[5] = $textItem->getId();
						$items[6] = $textItem->getContent();
					}
				}
			}
			else
			{
				$items = array( $langid, $item->getType(), $item->getCode(), 'default', $textTypeItem->getCode(), '', '' );
			}

			$contentItem->add( $items );
		}
	}


	/**
	 * Inits container for storing export files.
	 *
	 * @param string $resource Path to resource
	 * @return MW_Container_Interface Container item
	 */
	protected function _initContainer( $resource )
	{
		return new MW_Container_Zip( $resource, 'CSV' );
	}
}
<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 * @package MShop
 * @subpackage Stock
 */


namespace Aimeos\MShop\Stock\Manager;


/**
 * Default stock manager implementation.
 *
 * @package MShop
 * @subpackage Stock
 */
class Standard
	extends \Aimeos\MShop\Common\Manager\Base
	implements \Aimeos\MShop\Stock\Manager\Iface, \Aimeos\MShop\Common\Manager\Factory\Iface
{
	private array $searchConfig = [
		'stock.type' => [
			'label' => 'Type',
			'internalcode' => 'type',
		],
		'stock.productid' => [
			'label' => 'Product ID',
			'internalcode' => 'prodid',
		],
		'stock.stocklevel' => [
			'label' => 'Stock level',
			'internalcode' => 'stocklevel',
			'type' => 'float',
		],
		'stock.dateback' => [
			'label' => 'Back in stock date/time',
			'internalcode' => 'backdate',
			'type' => 'datetime',
		],
		'stock.timeframe' => [
			'label' => 'Delivery time frame',
			'internalcode' => 'timeframe',
		],
	];


	/**
	 * Creates a new empty item instance
	 *
	 * @param array $values Values the item should be initialized with
	 * @return \Aimeos\MShop\Stock\Item\Iface New stock item object
	 */
	public function create( array $values = [] ) : \Aimeos\MShop\Common\Item\Iface
	{
		$values['stock.siteid'] = $values['stock.siteid'] ?? $this->context()->locale()->getSiteId();
		return new \Aimeos\MShop\Stock\Item\Standard( 'stock.', $values );
	}



	/**
	 * Decreases the stock level for the given product codes/quantity pairs and type
	 *
	 * @param array $pairs Associative list of product codes as keys and quantities as values
	 * @param string $type Unique code of the stock type
	 * @return \Aimeos\MShop\Stock\Manager\Iface Manager object for chaining method calls
	 */
	public function decrease( iterable $pairs, string $type = 'default' ) : \Aimeos\MShop\Stock\Manager\Iface
	{
		$context = $this->context();
		$translations = ['stock.siteid' => '"siteid"'];
		$types = ['stock.siteid' => \Aimeos\Base\Criteria\SQL::type( 'string' )];

		$level = \Aimeos\MShop\Locale\Manager\Base::SITE_ALL;
		$level = $context->config()->get( 'mshop/stock/manager/sitemode', $level );

		$search = $this->object()->filter();
		$search->setConditions( $this->siteCondition( 'stock.siteid', $level ) );
		$conditions = $search->getConditionSource( $types, $translations );

		$conn = $context->db( $this->getResourceName() );

		/** mshop/stock/manager/stocklevel/mysql
		 * Increases or decreases the stock level for the given product and type code
		 *
		 * @see mshop/stock/manager/stocklevel/ansi
		 */

		/** mshop/stock/manager/stocklevel/ansi
		 * Increases or decreases the stock level for the given product and type code
		 *
		 * The stock level is decreased for the ordered products each time
		 * an order is placed by a customer successfully. Also, updates
		 * from external sources like ERP systems can increase the stock
		 * level of a product if no absolute values are set via save()
		 * instead.
		 *
		 * The stock level must be from one of the sites that are configured
		 * via the context item. If the current site is part of a tree of
		 * sites, the statement can increase or decrease stock levels from
		 * the current site and all parent sites if the stock level is
		 * inherited by one of the parent sites.
		 *
		 * Each time the stock level is updated, the modify date/time is
		 * set to the current timestamp and the editor field is updated.
		 *
		 * @param string SQL statement for increasing/decreasing the stock level
		 * @since 2017.01
		 * @see mshop/stock/manager/insert/ansi
		 * @see mshop/stock/manager/update/ansi
		 * @see mshop/stock/manager/newid/ansi
		 * @see mshop/stock/manager/delete/ansi
		 * @see mshop/stock/manager/search/ansi
		 * @see mshop/stock/manager/count/ansi
		 */
		$path = 'mshop/stock/manager/stocklevel';

		foreach( $pairs as $prodid => $qty )
		{
			$stmt = $conn->create( str_replace( ':cond', $conditions, $this->getSqlConfig( $path ) ) );

			$stmt->bind( 1, $qty, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
			$stmt->bind( 2, $context->datetime() ); //mtime
			$stmt->bind( 3, $context->editor() );
			$stmt->bind( 4, $prodid );
			$stmt->bind( 5, $type );

			$stmt->execute()->finish();
		}

		return $this;
	}


	/**
	 * Returns the additional column/search definitions
	 *
	 * @return array Associative list of column names as keys and items implementing \Aimeos\Base\Criteria\Attribute\Iface
	 */
	public function getSaveAttributes() : array
	{
		return $this->createAttributes( $this->searchConfig );
	}


	/**
	 * Increases the stock level for the given product codes/quantity pairs and type
	 *
	 * @param array $pairs Associative list of product codes as keys and quantities as values
	 * @param string $type Unique code of the type
	 * @return \Aimeos\MShop\Stock\Manager\Iface Manager object for chaining method calls
	 */
	public function increase( iterable $pairs, string $type = 'default' ) : \Aimeos\MShop\Stock\Manager\Iface
	{
		foreach( $pairs as $prodid => $qty ) {
			$pairs[$prodid] = -$qty;
		}

		return $this->object()->decrease( $pairs, $type );
	}


	/**
	 * Returns the prefix for the item properties and search keys.
	 *
	 * @return string Prefix for the item properties and search keys
	 */
	protected function prefix() : string
	{
		return 'stock.';
	}


	/** mshop/stock/manager/name
	 * Class name of the used stock manager implementation
	 *
	 * Each default manager can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the manager factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\MShop\Stock\Manager\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\MShop\Stock\Manager\Mymanager
	 *
	 * then you have to set the this configuration option:
	 *
	 *  mshop/stock/manager/name = Mymanager
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyManager"!
	 *
	 * @param string Last part of the class name
	 * @since 2020.10
	 */

	/** mshop/stock/manager/decorators/excludes
	 * Excludes decorators added by the "common" option from the stock manager
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "mshop/common/manager/decorators/default" before they are wrapped
	 * around the stock manager.
	 *
	 *  mshop/stock/manager/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\MShop\Common\Manager\Decorator\*") added via
	 * "mshop/common/manager/decorators/default" for the stock manager.
	 *
	 * @param array List of decorator names
	 * @since 2020.10
	 * @see mshop/common/manager/decorators/default
	 * @see mshop/stock/manager/decorators/global
	 * @see mshop/stock/manager/decorators/local
	 */

	/** mshop/stock/manager/decorators/global
	 * Adds a list of globally available decorators only to the stock manager
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\MShop\Common\Manager\Decorator\*") around the stock manager.
	 *
	 *  mshop/stock/manager/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\MShop\Common\Manager\Decorator\Decorator1" only to the stock
	 * manager.
	 *
	 * @param array List of decorator names
	 * @since 2020.10
	 * @see mshop/common/manager/decorators/default
	 * @see mshop/stock/manager/decorators/excludes
	 * @see mshop/stock/manager/decorators/local
	 */

	/** mshop/stock/manager/decorators/local
	 * Adds a list of local decorators only to the stock manager
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\MShop\Stock\Manager\Decorator\*") around the stock manager.
	 *
	 *  mshop/stock/manager/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\MShop\Stock\Manager\Decorator\Decorator2" only to the stock
	 * manager.
	 *
	 * @param array List of decorator names
	 * @since 2020.10
	 * @see mshop/common/manager/decorators/default
	 * @see mshop/stock/manager/decorators/excludes
	 * @see mshop/stock/manager/decorators/global
	 */

	/** mshop/stock/manager/resource
	 * Name of the database connection resource to use
	 *
	 * You can configure a different database connection for each data domain
	 * and if no such connection name exists, the "db" connection will be used.
	 * It's also possible to use the same database connection for different
	 * data domains by configuring the same connection name using this setting.
	 *
	 * @param string Database connection name
	 * @since 2023.04
	 */


	/** mshop/stock/manager/delete/mysql
	 * Deletes the items matched by the given IDs from the database
	 *
	 * @see mshop/stock/manager/delete/ansi
	 */

	/** mshop/stock/manager/delete/ansi
	 * Deletes the items matched by the given IDs from the database
	 *
	 * Removes the records specified by the given IDs from the stock database.
	 * The records must be from the site that is configured via the
	 * context item.
	 *
	 * The ":cond" placeholder is replaced by the name of the ID column and
	 * the given ID or list of IDs while the site ID is bound to the question
	 * mark.
	 *
	 * The SQL statement should conform to the ANSI standard to be
	 * compatible with most relational database systems. This also
	 * includes using double quotes for table and column names.
	 *
	 * @param string SQL statement for deleting items
	 * @since 2020.10
	 * @see mshop/stock/manager/insert/ansi
	 * @see mshop/stock/manager/update/ansi
	 * @see mshop/stock/manager/newid/ansi
	 * @see mshop/stock/manager/search/ansi
	 * @see mshop/stock/manager/count/ansi
	 */

	/** mshop/stock/manager/submanagers
	 * List of manager names that can be instantiated by the stock manager
	 *
	 * Managers provide a generic interface to the underlying storage.
	 * Each manager has or can have sub-managers caring about particular
	 * aspects. Each of these sub-managers can be instantiated by its
	 * parent manager using the getSubManager() method.
	 *
	 * The search keys from sub-managers can be normally used in the
	 * manager as well. It allows you to search for items of the manager
	 * using the search keys of the sub-managers to further limit the
	 * retrieved list of items.
	 *
	 * @param array List of sub-manager names
	 * @since 2020.10
	 */

	/** mshop/stock/manager/insert/mysql
	 * Inserts a new stock record into the database table
	 *
	 * @see mshop/stock/manager/insert/ansi
	 */

	/** mshop/stock/manager/insert/ansi
	 * Inserts a new stock record into the database table
	 *
	 * Items with no ID yet (i.e. the ID is NULL) will be created in
	 * the database and the newly created ID retrieved afterwards
	 * using the "newid" SQL statement.
	 *
	 * The SQL statement must be a string suitable for being used as
	 * prepared statement. It must include question marks for binding
	 * the values from the stock item to the statement before they are
	 * sent to the database server. The number of question marks must
	 * be the same as the number of columns listed in the INSERT
	 * statement. The order of the columns must correspond to the
	 * order in the save() method, so the correct values are
	 * bound to the columns.
	 *
	 * The SQL statement should conform to the ANSI standard to be
	 * compatible with most relational database systems. This also
	 * includes using double quotes for table and column names.
	 *
	 * @param string SQL statement for inserting records
	 * @since 2020.10
	 * @see mshop/stock/manager/update/ansi
	 * @see mshop/stock/manager/newid/ansi
	 * @see mshop/stock/manager/delete/ansi
	 * @see mshop/stock/manager/search/ansi
	 * @see mshop/stock/manager/count/ansi
	 */

	/** mshop/stock/manager/update/mysql
	 * Updates an existing stock record in the database
	 *
	 * @see mshop/stock/manager/update/ansi
	 */

	/** mshop/stock/manager/update/ansi
	 * Updates an existing stock record in the database
	 *
	 * Items which already have an ID (i.e. the ID is not NULL) will
	 * be updated in the database.
	 *
	 * The SQL statement must be a string suitable for being used as
	 * prepared statement. It must include question marks for binding
	 * the values from the stock item to the statement before they are
	 * sent to the database server. The order of the columns must
	 * correspond to the order in the save() method, so the
	 * correct values are bound to the columns.
	 *
	 * The SQL statement should conform to the ANSI standard to be
	 * compatible with most relational database systems. This also
	 * includes using double quotes for table and column names.
	 *
	 * @param string SQL statement for updating records
	 * @since 2020.10
	 * @see mshop/stock/manager/insert/ansi
	 * @see mshop/stock/manager/newid/ansi
	 * @see mshop/stock/manager/delete/ansi
	 * @see mshop/stock/manager/search/ansi
	 * @see mshop/stock/manager/count/ansi
	 */

	/** mshop/stock/manager/newid/mysql
	 * Retrieves the ID generated by the database when inserting a new record
	 *
	 * @see mshop/stock/manager/newid/ansi
	 */

	/** mshop/stock/manager/newid/ansi
	 * Retrieves the ID generated by the database when inserting a new record
	 *
	 * As soon as a new record is inserted into the database table,
	 * the database server generates a new and unique identifier for
	 * that record. This ID can be used for retrieving, updating and
	 * deleting that specific record from the table again.
	 *
	 * For MySQL:
	 *  SELECT LAST_INSERT_ID()
	 * For PostgreSQL:
	 *  SELECT currval('seq_mrul_id')
	 * For SQL Server:
	 *  SELECT SCOPE_IDENTITY()
	 * For Oracle:
	 *  SELECT "seq_mrul_id".CURRVAL FROM DUAL
	 *
	 * There's no way to retrive the new ID by a SQL statements that
	 * fits for most database servers as they implement their own
	 * specific way.
	 *
	 * @param string SQL statement for retrieving the last inserted record ID
	 * @since 2020.10
	 * @see mshop/stock/manager/insert/ansi
	 * @see mshop/stock/manager/update/ansi
	 * @see mshop/stock/manager/delete/ansi
	 * @see mshop/stock/manager/search/ansi
	 * @see mshop/stock/manager/count/ansi
	 */

	/** mshop/stock/manager/sitemode
	 * Mode how items from levels below or above in the site tree are handled
	 *
	 * By default, only items from the current site are fetched from the
	 * storage. If the ai-sites extension is installed, you can create a
	 * tree of sites. Then, this setting allows you to define for the
	 * whole stock domain if items from parent sites are inherited,
	 * sites from child sites are aggregated or both.
	 *
	 * Available constants for the site mode are:
	 * * 0 = only items from the current site
	 * * 1 = inherit items from parent sites
	 * * 2 = aggregate items from child sites
	 * * 3 = inherit and aggregate items at the same time
	 *
	 * You also need to set the mode in the locale manager
	 * (mshop/locale/manager/sitelevel) to one of the constants.
	 * If you set it to the same value, it will work as described but you
	 * can also use different modes. For example, if inheritance and
	 * aggregation is configured the locale manager but only inheritance
	 * in the domain manager because aggregating items makes no sense in
	 * this domain, then items wil be only inherited. Thus, you have full
	 * control over inheritance and aggregation in each domain.
	 *
	 * @param int Constant from Aimeos\MShop\Locale\Manager\Base class
	 * @since 2018.01
	 * @see mshop/locale/manager/sitelevel
	 */

	/** mshop/stock/manager/search/mysql
	 * Retrieves the records matched by the given criteria in the database
	 *
	 * @see mshop/stock/manager/search/ansi
	 */

	/** mshop/stock/manager/search/ansi
	 * Retrieves the records matched by the given criteria in the database
	 *
	 * Fetches the records matched by the given criteria from the stock
	 * database. The records must be from one of the sites that are
	 * configured via the context item. If the current site is part of
	 * a tree of sites, the SELECT statement can retrieve all records
	 * from the current site and the complete sub-tree of sites.
	 *
	 * As the records can normally be limited by criteria from sub-managers,
	 * their tables must be joined in the SQL context. This is done by
	 * using the "internaldeps" property from the definition of the ID
	 * column of the sub-managers. These internal dependencies specify
	 * the JOIN between the tables and the used columns for joining. The
	 * ":joins" placeholder is then replaced by the JOIN strings from
	 * the sub-managers.
	 *
	 * To limit the records matched, conditions can be added to the given
	 * criteria object. It can contain comparisons like column names that
	 * must match specific values which can be combined by AND, OR or NOT
	 * operators. The resulting string of SQL conditions replaces the
	 * ":cond" placeholder before the statement is sent to the database
	 * server.
	 *
	 * If the records that are retrieved should be ordered by one or more
	 * columns, the generated string of column / sort direction pairs
	 * replaces the ":order" placeholder.
	 *
	 * The number of returned records can be limited and can start at any
	 * number between the begining and the end of the result set. For that
	 * the ":size" and ":start" placeholders are replaced by the
	 * corresponding values from the criteria object. The default values
	 * are 0 for the start and 100 for the size value.
	 *
	 * The SQL statement should conform to the ANSI standard to be
	 * compatible with most relational database systems. This also
	 * includes using double quotes for table and column names.
	 *
	 * @param string SQL statement for searching items
	 * @since 2020.10
	 * @see mshop/stock/manager/insert/ansi
	 * @see mshop/stock/manager/update/ansi
	 * @see mshop/stock/manager/newid/ansi
	 * @see mshop/stock/manager/delete/ansi
	 * @see mshop/stock/manager/count/ansi
	 */

	/** mshop/stock/manager/count/mysql
	 * Counts the number of records matched by the given criteria in the database
	 *
	 * @see mshop/stock/manager/count/ansi
	 */

	/** mshop/stock/manager/count/ansi
	 * Counts the number of records matched by the given criteria in the database
	 *
	 * Counts all records matched by the given criteria from the stock
	 * database. The records must be from one of the sites that are
	 * configured via the context item. If the current site is part of
	 * a tree of sites, the statement can count all records from the
	 * current site and the complete sub-tree of sites.
	 *
	 * As the records can normally be limited by criteria from sub-managers,
	 * their tables must be joined in the SQL context. This is done by
	 * using the "internaldeps" property from the definition of the ID
	 * column of the sub-managers. These internal dependencies specify
	 * the JOIN between the tables and the used columns for joining. The
	 * ":joins" placeholder is then replaced by the JOIN strings from
	 * the sub-managers.
	 *
	 * To limit the records matched, conditions can be added to the given
	 * criteria object. It can contain comparisons like column names that
	 * must match specific values which can be combined by AND, OR or NOT
	 * operators. The resulting string of SQL conditions replaces the
	 * ":cond" placeholder before the statement is sent to the database
	 * server.
	 *
	 * Both, the strings for ":joins" and for ":cond" are the same as for
	 * the "search" SQL statement.
	 *
	 * Contrary to the "search" statement, it doesn't return any records
	 * but instead the number of records that have been found. As counting
	 * thousands of records can be a long running task, the maximum number
	 * of counted records is limited for performance reasons.
	 *
	 * The SQL statement should conform to the ANSI standard to be
	 * compatible with most relational database systems. This also
	 * includes using double quotes for table and column names.
	 *
	 * @param string SQL statement for counting items
	 * @since 2020.10
	 * @see mshop/stock/manager/insert/ansi
	 * @see mshop/stock/manager/update/ansi
	 * @see mshop/stock/manager/newid/ansi
	 * @see mshop/stock/manager/delete/ansi
	 * @see mshop/stock/manager/search/ansi
	 */
}

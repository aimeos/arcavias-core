<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2025
 * @package MShop
 * @subpackage Type
 */


namespace Aimeos\MShop\Type\Manager;


/**
 * Default type manager implementation.
 *
 * @package MShop
 * @subpackage Type
 */
class Standard
	extends \Aimeos\MShop\Common\Manager\Base
	implements \Aimeos\MShop\Type\Manager\Iface, \Aimeos\MShop\Common\Manager\Factory\Iface
{
	/**
	 * Creates a new empty item instance
	 *
	 * @param array $values Values the item should be initialized with
	 * @return \Aimeos\MShop\Type\Item\Iface New type item object
	 */
	public function create( array $values = [] ) : \Aimeos\MShop\Common\Item\Iface
	{
		$prefix = $this->prefix();
		$locale = $this->context()->locale();

		$values['.language'] = $locale->getLanguageId();
		$values[$prefix . 'for'] = $values[$prefix . 'for'] ?? $this->for();
		$values[$prefix . 'siteid'] = $values[$prefix . 'siteid'] ?? $locale->getSiteId();

		return new \Aimeos\MShop\Type\Item\Standard( $prefix, $values );
	}


	/**
	 * Creates a filter object.
	 *
	 * @param bool|null $default Add default criteria or NULL for relaxed default criteria
	 * @param bool $site TRUE for adding site criteria to limit items by the site of related items
	 * @return \Aimeos\Base\Criteria\Iface Returns the filter object
	 */
	public function filter( ?bool $default = false, bool $site = false ) : \Aimeos\Base\Criteria\Iface
	{
		$prefix = $this->prefix();
		$filter = $this->filterBase( substr( $prefix, 0, -1 ), $default );

		if( $prefix !== 'type.' ) {
			$filter->add( $prefix . 'for', '==', $this->for() );
		}

		return $filter;
	}


	/**
	 * Returns the item specified by its code and domain/type if necessary
	 *
	 * @param string $code Code of the item
	 * @param string[] $ref List of domains to fetch list items and referenced items for
	 * @param string|null $domain Domain of the item if necessary to identify the item uniquely
	 * @param string|null $type Type code of the item if necessary to identify the item uniquely
	 * @param bool|null $default Add default criteria or NULL for relaxed default criteria
	 * @return \Aimeos\MShop\Common\Item\Iface Item object
	 */
	public function find( string $code, array $ref = [], ?string $domain = 'product', ?string $type = null,
		?bool $default = false ) : \Aimeos\MShop\Common\Item\Iface
	{
		$prefix = $this->prefix();

		return $this->findBase( [
			$prefix . 'for' => $type ?: $this->for(),
			$prefix . 'domain' => $domain,
			$prefix . 'code' => $code
		], $ref, $default );
	}


	/**
	 * Returns the attributes that can be used for saving.
	 *
	 * @param bool $withsub Return also attributes of sub-managers if true
	 * @return \Aimeos\Base\Criteria\Attribute\Iface[] List of search attribute items
	 */
	public function getSaveAttributes( bool $withsub = true ) : array
	{
		$prefix = $this->prefix();

		return $this->createAttributes( [
			$prefix . 'for' => [
				'internalcode' => 'for',
				'label' => 'Related domain',
			],
			$prefix . 'label' => [
				'internalcode' => 'label',
				'label' => 'Type label',
			],
			$prefix . 'code' => [
				'internalcode' => 'code',
				'label' => 'Type code',
			],
			$prefix . 'domain' => [
				'internalcode' => 'domain',
				'label' => 'Type domain',
			],
			$prefix . 'position' => [
				'internalcode' => 'pos',
				'label' => 'Type position',
				'type' => 'int',
			],
			$prefix . 'status' => [
				'internalcode' => 'status',
				'label' => 'Type status',
				'type' => 'int',
			],
			$prefix . 'i18n' => [
				'internalcode' => 'i18n',
				'label' => 'Type localization',
				'type' => 'json',
				'public' => false,
			],
		] );
	}


	/**
	 * Returns the domain and sub-domains the items are for.
	 *
	 * @return string Domain and sub-domains, e.g. "product/property"
	 */
	protected function for() : string
	{
		return join( '/', array_slice( $this->type(), 0, -1 ) ) ?: 'type';
	}


	/**
	 * Returns the prefix used for the item keys.
	 *
	 * @return string Item key prefix
	 */
	protected function prefix() : string
	{
		return join( '.', $this->type() ) . '.';
	}


	/**
	 * Returns the name of the used table
	 *
	 * @return string Table name e.g. "mshop_product_list_type"
	 */
	protected function table() : string
	{
		return 'mshop_type';
	}


	/** mshop/type/manager/name
	 * Class name of the used type manager implementation
	 *
	 * Each default manager can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the manager factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\MShop\Type\Manager\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\MShop\Type\Manager\Mymanager
	 *
	 * then you have to set the this configuration option:
	 *
	 *  mshop/type/manager/name = Mymanager
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
	 * @since 2025.01
	 */

	/** mshop/type/manager/decorators/excludes
	 * Excludes decorators added by the "common" option from the type manager
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "mshop/common/manager/decorators/default" before they are wrapped
	 * around the type manager.
	 *
	 *  mshop/type/manager/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\MShop\Common\Manager\Decorator\*") added via
	 * "mshop/common/manager/decorators/default" for the type manager.
	 *
	 * @param array List of decorator names
	 * @since 2025.01
	 * @see mshop/common/manager/decorators/default
	 * @see mshop/type/manager/decorators/global
	 * @see mshop/type/manager/decorators/local
	 */

	/** mshop/type/manager/decorators/global
	 * Adds a list of globally available decorators only to the type manager
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\MShop\Common\Manager\Decorator\*") around the type manager.
	 *
	 *  mshop/type/manager/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\MShop\Common\Manager\Decorator\Decorator1" only to the type
	 * manager.
	 *
	 * @param array List of decorator names
	 * @since 2025.01
	 * @see mshop/common/manager/decorators/default
	 * @see mshop/type/manager/decorators/excludes
	 * @see mshop/type/manager/decorators/local
	 */

	/** mshop/type/manager/decorators/local
	 * Adds a list of local decorators only to the type manager
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\MShop\Type\Manager\Decorator\*") around the type manager.
	 *
	 *  mshop/type/manager/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\MShop\Type\Manager\Decorator\Decorator2" only to the type
	 * manager.
	 *
	 * @param array List of decorator names
	 * @since 2025.01
	 * @see mshop/common/manager/decorators/default
	 * @see mshop/type/manager/decorators/excludes
	 * @see mshop/type/manager/decorators/global
	 */

	/** mshop/type/manager/resource
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


	/** mshop/type/manager/delete/mysql
	 * Deletes the items matched by the given IDs from the database
	 *
	 * @see mshop/type/manager/delete/ansi
	 */

	/** mshop/type/manager/delete/ansi
	 * Deletes the items matched by the given IDs from the database
	 *
	 * Removes the records specified by the given IDs from the type database.
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
	 * @since 2025.01
	 * @see mshop/type/manager/insert/ansi
	 * @see mshop/type/manager/update/ansi
	 * @see mshop/type/manager/newid/ansi
	 * @see mshop/type/manager/search/ansi
	 * @see mshop/type/manager/count/ansi
	 */

	/** mshop/type/manager/submanagers
	 * List of manager names that can be instantiated by the type manager
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
	 * @since 2025.01
	 */

	/** mshop/type/manager/insert/mysql
	 * Inserts a new type record into the database table
	 *
	 * @see mshop/type/manager/insert/ansi
	 */

	/** mshop/type/manager/insert/ansi
	 * Inserts a new type record into the database table
	 *
	 * Items with no ID yet (i.e. the ID is NULL) will be created in
	 * the database and the newly created ID retrieved afterwards
	 * using the "newid" SQL statement.
	 *
	 * The SQL statement must be a string suitable for being used as
	 * prepared statement. It must include question marks for binding
	 * the values from the type item to the statement before they are
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
	 * @since 2025.01
	 * @see mshop/type/manager/update/ansi
	 * @see mshop/type/manager/newid/ansi
	 * @see mshop/type/manager/delete/ansi
	 * @see mshop/type/manager/search/ansi
	 * @see mshop/type/manager/count/ansi
	 */

	/** mshop/type/manager/update/mysql
	 * Updates an existing type record in the database
	 *
	 * @see mshop/type/manager/update/ansi
	 */

	/** mshop/type/manager/update/ansi
	 * Updates an existing type record in the database
	 *
	 * Items which already have an ID (i.e. the ID is not NULL) will
	 * be updated in the database.
	 *
	 * The SQL statement must be a string suitable for being used as
	 * prepared statement. It must include question marks for binding
	 * the values from the type item to the statement before they are
	 * sent to the database server. The order of the columns must
	 * correspond to the order in the save() method, so the
	 * correct values are bound to the columns.
	 *
	 * The SQL statement should conform to the ANSI standard to be
	 * compatible with most relational database systems. This also
	 * includes using double quotes for table and column names.
	 *
	 * @param string SQL statement for updating records
	 * @since 2025.01
	 * @see mshop/type/manager/insert/ansi
	 * @see mshop/type/manager/newid/ansi
	 * @see mshop/type/manager/delete/ansi
	 * @see mshop/type/manager/search/ansi
	 * @see mshop/type/manager/count/ansi
	 */

	/** mshop/type/manager/newid/mysql
	 * Retrieves the ID generated by the database when inserting a new record
	 *
	 * @see mshop/type/manager/newid/ansi
	 */

	/** mshop/type/manager/newid/ansi
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
	 * @since 2025.01
	 * @see mshop/type/manager/insert/ansi
	 * @see mshop/type/manager/update/ansi
	 * @see mshop/type/manager/delete/ansi
	 * @see mshop/type/manager/search/ansi
	 * @see mshop/type/manager/count/ansi
	 */

	/** mshop/type/manager/sitemode
	 * Mode how items from levels below or above in the site tree are handled
	 *
	 * By default, only items from the current site are fetched from the
	 * storage. If the ai-sites extension is installed, you can create a
	 * tree of sites. Then, this setting allows you to define for the
	 * whole type domain if items from parent sites are inherited,
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

	/** mshop/type/manager/search/mysql
	 * Retrieves the records matched by the given criteria in the database
	 *
	 * @see mshop/type/manager/search/ansi
	 */

	/** mshop/type/manager/search/ansi
	 * Retrieves the records matched by the given criteria in the database
	 *
	 * Fetches the records matched by the given criteria from the type
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
	 * @since 2025.01
	 * @see mshop/type/manager/insert/ansi
	 * @see mshop/type/manager/update/ansi
	 * @see mshop/type/manager/newid/ansi
	 * @see mshop/type/manager/delete/ansi
	 * @see mshop/type/manager/count/ansi
	 */

	/** mshop/type/manager/count/mysql
	 * Counts the number of records matched by the given criteria in the database
	 *
	 * @see mshop/type/manager/count/ansi
	 */

	/** mshop/type/manager/count/ansi
	 * Counts the number of records matched by the given criteria in the database
	 *
	 * Counts all records matched by the given criteria from the type
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
	 * @since 2025.01
	 * @see mshop/type/manager/insert/ansi
	 * @see mshop/type/manager/update/ansi
	 * @see mshop/type/manager/newid/ansi
	 * @see mshop/type/manager/delete/ansi
	 * @see mshop/type/manager/search/ansi
	 */
}

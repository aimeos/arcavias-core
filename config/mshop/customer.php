<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
 */


return array(
	'manager' => array(
		'address' => array(
			'clear' => array(
				'ansi' => '
					DELETE FROM ":table"
					WHERE :cond AND "siteid" LIKE ?
				'
			),
			'delete' => array(
				'ansi' => '
					DELETE FROM ":table"
					WHERE :cond AND ( "siteid" LIKE ? OR "siteid" = ? )
				'
			),
			'insert' => array(
				'ansi' => '
					INSERT INTO ":table" ( :names
						"mtime", "editor", "siteid", "ctime"
					) VALUES ( :values
						?, ?, ?, ?
					)
				'
			),
			'update' => array(
				'ansi' => '
					UPDATE ":table"
					SET :names
						"mtime" = ?, "editor" = ?
					WHERE ( "siteid" LIKE ? OR "siteid" = ? ) AND "id" = ?
				'
			),
		),
		'aggregate' => array(
			'ansi' => '
				SELECT :keys, :type("val") AS "value"
				FROM (
					SELECT :acols, :val AS "val"
					FROM "mshop_customer" mcus
					:joins
					WHERE :cond
					GROUP BY mcus.id, :cols, :val
					ORDER BY mcus.id DESC
					OFFSET :start ROWS FETCH NEXT :size ROWS ONLY
				) AS list
				GROUP BY :keys
			',
			'mysql' => '
				SELECT :keys, :type("val") AS "value"
				FROM (
					SELECT :acols, :val AS "val"
					FROM "mshop_customer" mcus
					:joins
					WHERE :cond
					GROUP BY mcus.id, :cols, :val
					ORDER BY mcus.id DESC
					LIMIT :size OFFSET :start
				) AS list
				GROUP BY :keys
			'
		),
		'clear' => array(
			'ansi' => '
				DELETE FROM "mshop_customer"
				WHERE :cond AND "siteid" LIKE ?
			',
		),
		'delete' => array(
			'ansi' => '
				DELETE FROM "mshop_customer"
				WHERE :cond AND ( "siteid" LIKE ? OR "siteid" = ? )
			'
		),
		'insert' => array(
			'ansi' => '
				INSERT INTO "mshop_customer" ( :names
					"label", "code", "company", "vatid", "salutation", "title",
					"firstname", "lastname", "address1", "address2", "address3",
					"postal", "city", "state", "countryid", "langid", "telephone",
					"mobile", "email", "telefax", "website", "longitude", "latitude", "birthday",
					"status", "vdate", "password", "mtime", "editor", "siteid", "ctime"
				) VALUES ( :values
					?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
				)
			'
		),
		'update' => array(
			'ansi' => '
				UPDATE "mshop_customer"
				SET :names
					"label" = ?, "code" = ?, "company" = ?, "vatid" = ?,
					"salutation" = ?, "title" = ?, "firstname" = ?, "lastname" = ?,
					"address1" = ?, "address2" = ?, "address3" = ?, "postal" = ?,
					"city" = ?, "state" = ?, "countryid" = ?, "langid" = ?, "telephone" = ?,
					"mobile" = ?, "email" = ?, "telefax" = ?, "website" = ?,
					"longitude" = ?, "latitude" = ?, "birthday" = ?, "status" = ?,
					"vdate" = ?, "password" = ?, "mtime" = ?, "editor" = ?
				WHERE ( "siteid" LIKE ? OR "siteid" = ? ) AND "id" = ?
			'
		),
		'search' => array(
			'ansi' => '
				SELECT :columns
				FROM "mshop_customer" mcus
				:joins
				WHERE :cond
				GROUP BY :group
				ORDER BY :order
				OFFSET :start ROWS FETCH NEXT :size ROWS ONLY
			',
			'mysql' => '
				SELECT :columns
				FROM "mshop_customer" mcus
				:joins
				WHERE :cond
				GROUP BY :group
				ORDER BY :order
				LIMIT :size OFFSET :start
			'
		),
		'count' => array(
			'ansi' => '
				SELECT COUNT(*) AS "count"
				FROM (
					SELECT mcus."id"
					FROM "mshop_customer" mcus
					:joins
					WHERE :cond
					GROUP BY mcus."id"
					ORDER BY mcus."id"
					OFFSET 0 ROWS FETCH NEXT 10000 ROWS ONLY
				) AS list
			',
			'mysql' => '
				SELECT COUNT(*) AS "count"
				FROM (
					SELECT mcus."id"
					FROM "mshop_customer" mcus
					:joins
					WHERE :cond
					GROUP BY mcus."id"
					ORDER BY mcus."id"
					LIMIT 10000 OFFSET 0
				) AS list
			'
		),
		'newid' => array(
			'db2' => 'SELECT IDENTITY_VAL_LOCAL()',
			'mysql' => 'SELECT LAST_INSERT_ID()',
			'oracle' => 'SELECT mshop_customer_seq.CURRVAL FROM DUAL',
			'pgsql' => 'SELECT lastval()',
			'sqlite' => 'SELECT last_insert_rowid()',
			'sqlsrv' => 'SELECT @@IDENTITY',
			'sqlanywhere' => 'SELECT @@IDENTITY',
		),
	),
);

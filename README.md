# php-libsqlitedriver

This library provides abstraction methods for common operations on SQLite databases like `SELECT`, `UPDATE`, and `INSERT` using method chaining for the various SQLite features.

For example:
```php
SQLite->for(string $table)
  ->with(?array $model)
  ->where(?array ...$conditions)
  ->order(?array $order_by)
  ->limit(int|array|null $limit)
  ->select(array $columns): array|bool;
```
which would be equivalent to the following in SQLite:
```sql
SELECT $columns FROM $table WHERE $filter ORDER BY $order_by LIMIT $limit;
```

> [!IMPORTANT]
> This library is built on top of the PHP [`SQLite3 Extension`](https://www.php.net/manual/en/book.sqlite3.php) and requires PHP 8.0 or newer.

## Install from composer

```
composer require victorwesterlund/libSQLitedriver
```

```php
use libsqlitedriver/SQLite;
```

# Example / Documentation

Available statements
Statement|Method
--|--
`SELECT`|[`select()`](#select)
`UPDATE`|[`update()`](#update)
`INSERT`|[`insert()`](#insert)
`DELETE`|[`delete()`](#delete)
`WHERE`|[`where()`](#where)
`ORDER BY`|[`order()`](#order-by)
`LIMIT`|[`limit()`](#limit)

----

`Example table name: beverages`
id|beverage_type|beverage_name|beverage_size
--|--|--|--
0|coffee|cappuccino|10
1|coffee|black|15
2|tea|green|10
3|tea|black|15

```php
use libSQLitedriver\SQLite;

// Pass through: https://www.php.net/manual/en/sqlite3.construct.php
$db = new SQLite($filename = ":memory:");
```

All executor methods [`select()`](#select), [`update()`](#update), and [`insert()`](#insert) will return a [`SQLite3Result`](https://www.php.net/manual/en/class.SQLitei-result.php) object or boolean.

# FOR

```php
SQLite->for(
  string $table
): self;
```

All queries start by chaining the `for(string $table)` method. This will define which database table the current query should be executed on.

*Example:*
```php
SQLite->for("beverages")->select("beverage_type");
```

# SELECT

Chain `SQLite->select()` anywhere after a [`SQLite->for()`](#for) to retrieve columns from a database table.

Pass an associative array of strings, CSV string, or null to this method to filter columns.

```php
SQLite->select(
  array|string|null $columns
): SQLite3Result|bool;
```

In most cases you probably want to select with a constraint. Chain the [`where()`](#where) method before `select()` to filter the query

### Example
```php
$beverages = SQLite->for("beverages")->select(["beverage_name", "beverage_size"]); // SELECT beverage_name, beverage_size FROM beverages
```
```
[
  [
    "beverage_name" => "cappuccino",
    "beverage_size" => 10
  ],
  [
    "beverage_name" => "black",
    "beverage_size" => 15
  ],
  // ...etc
]
```

## Flatten array to single dimension

If you don't want an array of arrays and would instead like to access each key value pair directly. Chain the `SQLite->flatten()` anywhere before `SQLite->select()`.
This will return the key value pairs of the first entry directly.

> **Note**
> This method will not set `LIMIT 1` for you. It is recommended to chain `SQLite->limit(1)` anywhere before `SQLite->select()`. [You can read more about it here](https://github.com/VictorWesterlund/php-libSQLitedriver/issues/14)

```php
$coffee = SQLite->for("beverages")->limit(1)->flatten()->select(["beverage_name", "beverage_size"]); // SELECT beverage_name, beverage_size FROM beverages WHERE beverage_type = "coffee" LIMIT 1
```
```php
[
  "beverage_name" => "cappuccino",
  "beverage_size" => 10
]
```

# INSERT

Chain `SQLite->insert()` anywhere after a [`SQLite->for()`](#for) to append a new row to a database table.

Passing a sequential array to `insert()` will assume that you wish to insert data for all defined columns in the table. Pass an associative array of `[column_name => value]` to INSERT data for specific columns (assuming the other columns have a [DEFAULT](https://dev.SQLite.com/doc/refman/8.0/en/data-type-defaults.html) value defined).

```php
SQLite->insert(
  // Array of values to INSERT
  array $values
): SQLite3Result|bool
// Returns true if row was inserted
```

#### Example

```php
SQLite->for("beverages")->insert([
  null,
  "coffee",
  "latte",
  10
]);
// INSERT INTO beverages VALUES (null, "coffee", "latte", 10);
```
```
true
```

# DELETE

Chain `SQLite->delete()` anywhere after a [`SQLite->for()`](#for) to remove a row or rows from the a database table.

```php
SQLite->delete(
  array ...$conditions
): SQLite3Result|bool
// Returns true if at least one row was deleted
```

This method takes at least one [`SQLite->where()`](#where)-syntaxed argument to determine which row or rows to delete. Refer to the [`SQLite->where()`](#where) section for more information.

#### Example

```php
SQLite->for("beverages")->insert([
  null,
  "coffee",
  "latte",
  10
]);
// INSERT INTO beverages VALUES (null, "coffee", "latte", 10);
```
```
true
```

# UPDATE

Chain `SQLite->update()` anywhere after a [`SQLite->for()`](#for) to modify existing rows in a database table.

```php
SQLite->update(
  // Key, value array of column names and values to update
  array $fields,
): SQLite3Result|bool;
// Returns true if at least 1 row was changed
```

### Example
```php
SQLite->for("beverages")->update(["beverage_size" => 10]); // UPDATE beverages SET beverage_size = 10
```
```php
true
```

In most cases you probably want to UPDATE against a constaint. Chain a [`where()`](#where) method before [`SQLite->update()`](#update) to set constraints


# WHERE

Filter a [`SQLite->select()`](#select) or [`SQLite->update()`](#update) method by chaining the `SQLite->where()` method anywhere before it. The [`SQLite->delete()`](#delete) executor method also uses the same syntax for its arguments.

Each key, value pair will be `AND` constrained against each other.

```php
SQLite->where(
  ?array ...$conditions
): self;
```

### Example
```php
$coffee = SQLite->for("beverages")->where(["beverage_type" => "coffee"])->select(["beverage_name", "beverage_size"]); // SELECT beverage_name, beverage_size FROM beverages WHERE (beverage_type = "coffee");
```
```php
[
  [
    "beverage_name" => "cappuccino",
    "beverage_size" => 10
  ],
  [
    "beverage_name" => "black",
    "beverage_size" => 15
  ]
]
```

## Capture groups

### AND

Add additional key value pairs to an array passed to `where()` and they will all be compared as AND with each other.

```php
SQLite->where([
  "beverage_type" => "coffee",
  "beverage_size" => 15
]);
```
```sql
WHERE (beverage_type = 'coffee' AND beverage_size = 15)
```

### OR

Passing an additional array of key values as an argument will OR it with all other arrays passed.

```php
$filter1 = [
  "beverage_type" => "coffee",
  "beverage_size" => 15
];

$filter2 = [
  "beverage_type" => "tea",
  "beverage_name" => "black"
];

SQLite->where($filter1, $filter2, ...);
```
```sql
WHERE (beverage_type = 'coffee' AND beverage_size = 15) OR (beverage_type = 'tea' AND beverage_name = 'black')
```

# ORDER BY

Chain the `SQLite->order()` method before a [`SQLite->select()`](#select) statement to order by a specific column

```php
SQLite->order(
  ?array $order_by
): self;
```

```php
$coffee = SQLite->for("beverages")->order(["beverage_name" => "ASC"])->select(["beverage_name", "beverage_size"]); // SELECT beverage_name, beverage_size FROM beverages ORDER BY beverage_name ASC
```
```php
[
  [
    "beverage_name" => "tea",
    "beverage_size" => 10
  ],
  [
    "beverage_name" => "tea",
    "beverage_size" => 15
  ],
  // ...etc for "beverage_name = coffee"
]
```

# LIMIT

Chain the `limit()` method before a [`SQLite->select()`](#select) statement to limit the amount of columns returned

```php
SQLite->limit(
  ?int $limit,
  ?int $offset = null
): self;
```

> **Note**
> You can also flatten to a single dimensional array from the first entity by chaining [`SQLite->flatten()`](#flatten-array-to-single-dimension)

## Passing a single integer argument
This will simply `LIMIT` the results returned to the integer passed

```php
$coffee = SQLite->for("beverages")->limit(1)->select(["beverage_name", "beverage_size"]); // SELECT beverage_name, beverage_size FROM beverages WHERE beverage_type = "coffee" LIMIT 1
```
```php
[
  [
    "beverage_name" => "cappuccino",
    "beverage_size" => 10
  ]
]
```

## Passing two integer arguments
This will `OFFSET` and `LIMIT` the results returned. The first argument will be the `LIMIT` and the second argument will be its `OFFSET`.

```php
$coffee = SQLite->for("beverages")->limit(3, 2)->select(["beverage_name", "beverage_size"]); // SELECT beverage_name, beverage_size FROM beverages LIMIT 3 OFFSET 2
```
```php
[
  [
    "beverage_name" => "tea",
    "beverage_size" => 10
  ],
  [
    "beverage_name" => "tea",
    "beverage_size" => 15
  ],
  // ...etc
]
```

----

# Restrict affected/returned database columns to table model

Chain and pass an array to `SQLite->with()` before a `select()`, `update()`, or `insert()` method to limit which columns will be returned/affected. It will use the **values** of the array so it can be either sequential or associative.

**This method will cause `select()`, `update()`, and `insert()` to ignore any columns that are not present in the passed table model.**

You can remove an already set table model by passing `null` to `SQLite->with()`

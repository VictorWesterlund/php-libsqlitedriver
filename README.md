# php-libsqlitedriver

This library provides abstractions for parameter binding and result retrieval on SQLite(-like) databases in PHP. It is built on top of PHP [`SQLite3`](https://www.php.net/manual/en/book.sqlite3.php).

## Install with Composer

```
composer require victorwesterlund/libsqlitedriver
```

```php
use libsqlitedriver/SQLite;
```

## Usage

Connect to a SQLite database

```php
use libsqlitedriver/SQLite;

// You can also use ":memory:" to connect to an SQLite database in RAM
$db = new SQLite("./database.db");
```

Return matching rows from query (array of arrays)

```php
$sql = "SELECT foo FROM table WHERE bar = ? AND biz = ?;

$response = $db->return_array($sql, [
  "parameter_1",
  "parameter_2
];

// Example $response with two matching rows: [["hello"],["world"]]
```

Return boolean if query matched at least one row, or if != `SELECT` query was sucessful

```php
$sql = "INSERT INTO table (foo, bar) VALUES (?, ?);

$response = $db->return_bool($sql, [
  "baz",
  "qux"
];

// Example $response if sucessful: true
```

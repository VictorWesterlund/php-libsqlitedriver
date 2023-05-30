<?php

 	namespace libsqlitedriver;

	class SQLite extends \SQLite3 {
		function __construct(string $db = ":memory:", string $init = null) {
			$this->db_path = $db;

			// Run .sql file on first run of persistant db
			$run_init = false;

			// Set path to persistant db
			if ($this->db_path !== ":memory:") {
				// Get path to database without filename
				$path = explode("/", $this->db_path);
				array_pop($path);
				$path = implode("/", $path);

				// Check write permissions of database
				if (!is_writeable($path)) {
					throw new \Error("Permission denied: Can not write to directory '{$path}'");
				}
				
				// Database doesn't exist and an init file as been provided
				$run_init = !file_exists($db) && $init ? true : $run_init;
			}
			
			parent::__construct($db);

			if ($run_init) {
				$this->init_db($init);
			}
		}

		// Execute a prepared statement and SQLite3Result object
		private function run_query(string $query, mixed $values = []): \SQLite3Result|bool {
			$statement = $this->prepare($query);

			// Format optional placeholder "?" with values
			if (!empty($values)) {
				// Move single arguemnt into array
				if (!is_array($values)) {
					$values = [$values];
				}

				foreach ($values as $k => $value) {
					$statement->bindValue($k + 1, $value); // Index starts at 1
				}
			}

			// Return SQLite3Result object
			return $statement->execute();
		}

		// Execute SQL from a file
		private function exec_file(string $file): bool {
			return $this->exec(file_get_contents($file));
		}

		/* ---- */

		// Create comma separated list (CSV) from array
		private static function csv(array $values): string {
			return implode(",", $values);
		}

		// Create CSV from columns
		public static function columns(array|string $columns): string {
			return is_array($columns) 
				? (__CLASS__)::csv($columns)
				: $columns;
		}

		// Return CSV of '?' for use with prepared statements
		public static function values(array|string $values): string {
			return is_array($values) 
				? (__CLASS__)::csv(array_fill(0, count($values), "?"))
				: "?";
		}

		/* ---- */

		// Get result as column indexed array
		public function return_array(string $query, mixed $values = []): array {
			$result = $this->run_query($query, $values);
			$rows = [];

			if (is_bool($result)) {
				return [];
			}

			// Get each row from SQLite3Result
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$rows[] = $row;
			}

			return $rows;
		}

		// Get only whether a query was sucessful or not
		public function return_bool(string $query, mixed $values = []): bool {
			$result = $this->run_query($query, $values);

			if (is_bool($result)) {
				return $result;
			}

			// Get first row or return false
			$row = $result->fetchArray(SQLITE3_NUM);
			return $row !== false ? true : false;
		}

		/* ---- */

		// Initialize a fresh database with SQL from file
		private function init_db(string $init) {
			return $this->exec_file($init);
		}
	}

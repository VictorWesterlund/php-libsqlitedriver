<?php

	namespace libsqlitedriver\Driver;

	use \SQLite3;
	use \SQLite3Result;

	class DatabaseDriver extends SQLite3 {
		public function __construct(private string $database) {
			parent::__construct($database);
		}

		// Execute a prepared statement and SQLite3Result object
		private function run_query(string $query, mixed $values = []): SQLite3Result|bool {
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

		/* ---- */

		// Return rows as assoc array
		#[\ReturnTypeWillChange]
		public function exec(string $sql, mixed $params = null): array {
			$results = [];
			$query = $this->run_query($sql, $params);

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				$results[] = $result;
			}

			return $results;
		}

		// Returns true if rows were returned
		public function exec_bool(string $sql, mixed $params = null): bool {
			$query = $this->run_query($sql, $params);
			return $query->numColumns() > 0;
		}
	}
<?php
    namespace Matchla\Models;

    use Matchla\Config\Database;

    abstract class BaseModel {
        // tıpkı laravel'deki gibi. 
        protected \PDO $pdo;
        protected array $fillable = [];
        protected array $updatable = [];
        protected string $table = "";
        
        public function __construct() {
            $this->pdo = Database::getInstance()->getPDO();
        }
        
        public function find( array $conditions, array $columns = ['*']): ?array {

            $placeholders = array_map(fn($c) => "$c = ?", array_keys($conditions));

            $col = count($placeholders) === 1 ? $placeholders[0] : implode(" AND ", $placeholders);

            $cols = implode(", ", $columns);

            $stmt = $this->pdo->prepare("SELECT {$cols} FROM {$this->table} WHERE {$col}");
            $stmt->execute(array_values($conditions));
        
            return $stmt->fetch() ?: null;
        }

        public function findAll(array $columns = ['*']): array {
            $cols = implode(", ", $columns);
            $stmt = $this->pdo->prepare("SELECT {$cols} FROM {$this->table}");
            $stmt->execute();
            return $stmt->fetchAll();
        }

        public function delete(string $id): bool {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        }

        public function create(array $data): int {
            $columns = implode(", ", $this->fillable);
            $placeholders = implode(", ", array_fill(0, count($this->fillable), "?"));
            $values = array_map(fn($f) => $data[$f] ?? null, $this->fillable);

            $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ({$columns})
                VALUES ({$placeholders})");

            $stmt->execute($values);
            
            return $this->pdo->lastInsertId();
        }

        public function update(string $id, array $data): bool { 
            // güncellenmesi istenen alanlar
            $fields = array_filter($this->updatable, fn($f) => isset($data[$f]));

            // sql'e uygun hale getir
            $fieldsQuery = array_map(fn($f) => "$f = ?", $fields);

            // value'ları
            $values = array_values(array_map(fn($f) => $data[$f], $fields));
            $values[] = $id;

            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET " . implode(", ", $fieldsQuery) . " WHERE id = ?");
            $stmt->execute($values);

            return $stmt->rowCount() > 0;
        }
    }
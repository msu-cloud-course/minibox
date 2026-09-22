<?php

declare(strict_types=1);

namespace App\Models;

// A registered user (table "users").
class User extends Model
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $passwordHash,
    ) {
    }

    public static function find(int $id): ?self
    {
        $row = self::db()->query('SELECT * FROM users WHERE id = ?', [$id])->fetch();
        return $row === false ? null : self::fromRow($row);
    }

    public static function findByEmail(string $email): ?self
    {
        $row = self::db()->query('SELECT * FROM users WHERE email = ?', [$email])->fetch();
        return $row === false ? null : self::fromRow($row);
    }

    /** Save a new user. The password is stored only as a hash. */
    public static function create(string $email, string $password): self
    {
        $row = self::db()->query(
            'INSERT INTO users (email, password_hash) VALUES (?, ?) RETURNING *',
            [$email, password_hash($password, PASSWORD_DEFAULT)]
        )->fetch();

        return self::fromRow($row);
    }

    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    private static function fromRow(array $row): self
    {
        return new self((int) $row['id'], $row['email'], $row['password_hash']);
    }
}

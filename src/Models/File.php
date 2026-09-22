<?php

declare(strict_types=1);

namespace App\Models;

// An uploaded file (table "files"). The file itself lives in the upload folder (see Storage);
// the database keeps its name, type, size and owner.
// Every query is limited to one user, so nobody can see or touch other people's files.
class File extends Model
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $originalName,   // name on the user's computer
        public readonly string $storedName,     // random name in the upload folder
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly ?string $description,
        public readonly string $createdAt,      // UTC, e.g. "2026-09-17 14:05:00"
    ) {
    }

    /** @return self[] the user's files, newest first */
    public static function allForUser(int $userId): array
    {
        $rows = self::db()->query(
            'SELECT * FROM files WHERE user_id = ? ORDER BY created_at DESC, id DESC',
            [$userId]
        )->fetchAll();

        return array_map(self::fromRow(...), $rows);
    }

    /** One file, only if it belongs to the user. */
    public static function findForUser(int $id, int $userId): ?self
    {
        $row = self::db()->query('SELECT * FROM files WHERE id = ? AND user_id = ?', [$id, $userId])->fetch();
        return $row === false ? null : self::fromRow($row);
    }

    /** @return array{count: int, bytes: int} number of files and their total size */
    public static function totalsForUser(int $userId): array
    {
        $row = self::db()->query(
            'SELECT COUNT(*) AS count, COALESCE(SUM(size_bytes), 0) AS bytes FROM files WHERE user_id = ?',
            [$userId]
        )->fetch();

        return ['count' => (int) $row['count'], 'bytes' => (int) $row['bytes']];
    }

    public static function create(
        int $userId,
        string $originalName,
        string $storedName,
        string $mimeType,
        int $sizeBytes,
        ?string $description,
    ): self {
        $row = self::db()->query(
            'INSERT INTO files (user_id, original_name, stored_name, mime_type, size_bytes, description)
             VALUES (?, ?, ?, ?, ?, ?)
             RETURNING *',
            [$userId, $originalName, $storedName, $mimeType, $sizeBytes, $description]
        )->fetch();

        return self::fromRow($row);
    }

    public function delete(): void
    {
        self::db()->query('DELETE FROM files WHERE id = ?', [$this->id]);
    }

    /** "PNG", "PDF", ... for the list. */
    public function typeLabel(): string
    {
        $extension = strtoupper(pathinfo($this->originalName, PATHINFO_EXTENSION));
        return $extension !== '' ? $extension : 'FILE';
    }

    private static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['original_name'],
            $row['stored_name'],
            $row['mime_type'],
            (int) $row['size_bytes'],
            $row['description'],
            $row['created_at'],
        );
    }
}

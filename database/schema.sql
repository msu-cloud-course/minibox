-- MiniBox database schema (PostgreSQL).
-- Safe to run many times: it only creates what is missing.

CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,              -- made by password_hash(), never the real password
    created_at    TIMESTAMP    NOT NULL DEFAULT (NOW() AT TIME ZONE 'UTC')
);

CREATE TABLE IF NOT EXISTS files (
    id            SERIAL PRIMARY KEY,
    user_id       INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,  -- the owner
    original_name VARCHAR(255) NOT NULL,              -- name of the file on the user's computer
    stored_name   VARCHAR(64)  NOT NULL UNIQUE,       -- random name in the upload folder
    mime_type     VARCHAR(100) NOT NULL,
    size_bytes    BIGINT       NOT NULL,
    description   VARCHAR(255),
    created_at    TIMESTAMP    NOT NULL DEFAULT (NOW() AT TIME ZONE 'UTC')
);

-- Databases created by an older MiniBox version have no owner column yet.
-- Their old files get no owner, so nobody sees them.
ALTER TABLE files ADD COLUMN IF NOT EXISTS user_id INTEGER REFERENCES users(id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS files_user_id_idx ON files (user_id);

CREATE TABLE password_reset_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at VARCHAR(32) NOT NULL,
    used_at VARCHAR(32) DEFAULT NULL,
    created_at VARCHAR(32) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX password_reset_tokens_user_id_index ON password_reset_tokens (user_id);

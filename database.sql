-- Supabase SQL Editor
CREATE TABLE IF NOT EXISTS posts (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT NOT NULL,
    image_url TEXT,
    author TEXT DEFAULT 'XT4',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL
);

-- Seed admin (hash password sesuai variabel ADMIN_PASSWORD)
-- Ganti 'hash_dari_password' dengan hasil password_hash('password_dari_env', PASSWORD_BCRYPT)
INSERT INTO admins (username, password_hash) VALUES ('xt4admin', '$2y$12$...') ON CONFLICT DO NOTHING;

-- Storage bucket: buat melalui Supabase Dashboard dengan nama 'blog-images' (public)

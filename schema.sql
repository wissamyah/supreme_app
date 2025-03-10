CREATE TYPE user_role AS ENUM ('admin', 'moderator', 'operator');
CREATE TYPE transaction_type AS ENUM ('sale', 'payment', 'credit_note');
CREATE TYPE loading_status AS ENUM ('Pending', 'Partially Loaded', 'Fully Loaded');

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL,
    last_session TIMESTAMP,
    is_blocked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT REFERENCES categories(id) ON DELETE RESTRICT,
    physical_stock INT DEFAULT 0,
    booked_stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_products_category_id ON products(category_id);

CREATE TABLE production (
    id SERIAL PRIMARY KEY,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity INT NOT NULL,
    production_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_production_product_id ON production(product_id);
CREATE INDEX idx_production_date ON production(production_date);

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    state VARCHAR(50),
    balance DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_customers_name ON customers(name);

CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    customer_id INT REFERENCES customers(id) ON DELETE RESTRICT,
    sale_date DATE NOT NULL,
    total_amount DECIMAL(15, 2) NOT NULL,
    loading_status loading_status DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_sales_customer_id ON sales(customer_id);
CREATE INDEX idx_sales_date ON sales(sale_date);

CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    customer_id INT REFERENCES customers(id) ON DELETE CASCADE,
    type transaction_type NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    sale_id INT REFERENCES sales(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_transactions_customer_id ON transactions(customer_id);

CREATE TABLE sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INT REFERENCES sales(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity INT NOT NULL,
    rate DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_sale_items_sale_id ON sale_items(sale_id);

CREATE TABLE loadings (
    id SERIAL PRIMARY KEY,
    customer_id INT REFERENCES customers(id) ON DELETE RESTRICT,
    loading_date DATE NOT NULL,
    truck_number VARCHAR(50) NOT NULL,
    waybill VARCHAR(50),
    driver_name VARCHAR(50),
    driver_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_loadings_customer_id ON loadings(customer_id);

CREATE TABLE loading_items (
    id SERIAL PRIMARY KEY,
    loading_id INT REFERENCES loadings(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_loading_items_loading_id ON loading_items(loading_id);

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
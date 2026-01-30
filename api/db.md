+customer
-id
-full_name
-gender
-user_name
-email (nullable)
-phone
-password

+user
-id
-first_name
-last_name
-gender
-user_name
-phone
-email (nullable)
-password
-role (nullable)

+categories
-id
-name
-desc (nullable)
-slug

+dress_type
-id
-name
-desc (nullable)
-sort_order
-img (nullable)

+products
-id
-sku (Stock Keeping Unit)
-slug
-name
-desc
-base_price
-category_id
-dress_type_id

+colors
-id
-name
-hex_code

+sizes
-id
-name
-sort_order

+product_images
-id
-product_id
-image_url
-image_type
-sort_order

+product_variants
-id
-product_id
-sku
-color_id
-size_id
-quantity
-price (can null base price)
\*UNIQUE(product_id, color_id, size_id)

+order
-id
-customer_id
-order_date
-total_price
-shipping_province
-shipping_fee
-shipping_address
-shipping_phone
-payment_method
-payment_status ENUM(pending, paid, fail) default('pending')
-order_status ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending',
-created_at

+order_items
-id
-order_id
-product_id
-quantity
-price
-total GENERATED ALWAYS AS (price \* quantity) STORED

+vouchers
-id
-code (UNIQUE)
-discount_type
-discount_value
-min_order DECIMAL(10,2) DEFAULT 0,
-max_order int (100)
-used_count (0)
-expires_at
-is_active

+voucher_uses
-id
-voucher_id
-order_id
-customer_id
-discount_amount (total discount)
-used_date

/\*
If cross-device
\_/

<!-- +carts
-id
-customer_id
-product_id
-quantity

+wishlist
-id
-customer_id
-product_id -->

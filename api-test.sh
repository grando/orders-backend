#!/bin/bash

##
INSERT INTO `product` (`id`, `name`, `price`, `stock_level`) VALUES
(1,	'Penna blue',	0.60,	10),
(2,	'Penna rossa',	0.80,	5),
(3,	'Blocco Note',	2.99,	1);
##

# List all orders
curl -X 'GET' \
  'http://webapp.local/orders' \
  -H 'accept: application/json'

curl -X 'GET' \
  'http://webapp.local/orders' \
  -H 'accept: application/json' \
  -d '{
  "name": "order",
  "description": "descr",
  "page": 1,
  "limit": 10
}'


# Order details
curl -X 'GET' \
  'http://webapp.local/orders/1' \
  -H 'accept: application/json'

# Create new order
curl -X 'POST' \
  'http://webapp.local/orders' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "name": "New Order",
  "description": "Order description"
}'

# Update order
curl -X 'PUT' \
  'http://webapp.local/orders/1' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "name": "New Order updated",
  "description": "Order description updated"
}'


# Add product to order
curl -X 'PUT' \
  'http://webapp.local/orders/1/products/3' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{ "quantity": 1 }'


# Delete product quantity
curl -X 'DELETE' \
  'http://webapp.local/orders/1/products/3' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{ "quantity": 1 }'  
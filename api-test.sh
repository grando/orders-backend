#!/bin/bash

##
INSERT INTO `product` (`id`, `name`, `price`, `stock_level`) VALUES
(1,	'Penna blue',	0.60,	10),
(2,	'Penna rossa',	0.80,	3),
(3,	'Blocco Note',	2.99,	4);
##

curl -X 'GET' \
  'http://webapp.local/orders' \
  -H 'accept: application/json'

curl -X 'POST' \
  'http://webapp.local/orders' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "name": "New Order",
  "description": "Order description"
}'


curl -X 'PUT' \
  'http://webapp.local/orders/1/products/3' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{ "quantity": 1 }'
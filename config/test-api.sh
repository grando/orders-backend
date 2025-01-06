#!/bin/bash

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
  'http://webapp.local/orders/1' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{ 
  "name": "New Order name", 
  "description": "New Order description" 
}'


curl -X 'DELETE' 'http://webapp.local/orders/12' 




curl -X 'PUT' \
  'http://webapp.local/orders/1/products/3' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{ "quantity": 1 }'

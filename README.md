This repository contains the code resolving a provided technical challenge. The details are provided [here](./doc/Tech%20Test%20PHP%20Backend-1.pdf).

## Installation
The application use Symfony 6.4, PHP 8.1, and MySQL 8.0. All the environment is containerized using docker.

A makefile is provided to ease the installation process. The default way to install the project use docker-compose tools.

Run the following command to install the project:
```bash
make up
```
The first time, the command will take some time to download the docker images and build the project.

Use the following command to enter into the php container:
```bash
make php_shell
```

Complete the installation by running the following command after entering into the php container:

```bash
make make regenerate_dev
make console_regenerate_db_dev # generate the database
```

Before running the app, add the following line to your `/etc/hosts` file:
```bash
 127.0.0.1   webapp.local
```


## Tests
To run the tests, use the following command:

```bash
php bin/phpunit
```

Each time is needed to regenerate the test database, use the following command:

```bash
make console_regenerate_db_test
```

## API Documentation
The API documentation is available at the following URL: [http://webapp.local/api/doc](http://webapp.local/api/doc)

## Considerations about the developed solution
A important point about the developed solution is how the concurrency is handled. 
Each products has a stock level, and the concurrance orders could lead to a negative stock level.
To avoid this condition, a simple transaction is used to decrement or increment the stock level.
This approach is not the best one if we have a huge amount of orders to process simultaneously.
I use it because it is simple enough demo purpose.
Database transactions can be raplidly became a bottleneck in a high concurrency environment.
Other possible solutions are:

- use a message queue to process the orders asynchronously with single worker.
- use Redis to store the stock level, check it and update the tables using Doctrine, without using transactions.
- use Redis to store the stock level and the logging of the transactios, creating workers that asincronously sync the database

The complexity of the solution depends on the requirements and the expected load of the system.

## Considerations about the followed approach
I starting directly with the implementation of the solution; a best approch could be to start implementing the tests first and follwing the TDD approach.

I used Visual Studio Code as IDE and a paid version of Copilot to help me with the code generation. 
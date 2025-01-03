up:
	sudo docker compose -f docker-compose.yml up -d --build --remove-orphans
	sudo docker ps

down:
	sudo docker compose -f docker-compose.yml down

clean_up_containers:
	sudo docker compose down --volumes --rmi all
	sudo docker rm $(sudo docker ps -a --filter "name=webapp")

build_again_webapp:
	sudo docker compose build --no-cache webapp

rebuild_webapp: down build_again_webapp up

restart: down up

restart_db:
	sudo docker compose -f docker-compose.yml restart db

php_shell:
	sudo docker exec -ti -w /var/www/html webapp /bin/zsh

prepare_test:
	 php bin/console cache:clear --env=test
	 php bin/console doctrine:database:drop --force --env=test
	 php bin/console doctrine:database:create --env=test
	 php bin/console doctrine:schema:create --env=test

composer_reinstall:
	rm -rf vendor
	composer install
	php bin/console cache:clear --env=dev

regenerate_dev: composer_reinstall console_regenerate_db_test

reset_running_branch: regenerate_dev console_regenerate_db_test

# to use from into the php container
cc:
	php bin/console cache:clear

# php-cs-fixer
php-cs-fix:
	vendor/bin/php-cs-fixer fix
	
php-cs-fix-dry:
	vendor/bin/php-cs-fixer fix --dry-run -v

php-check-commented-code:
	vendor/bin/easy-ci check-commented-code src
	vendor/bin/easy-ci check-commented-code tests

# Define the name of the text file containing file paths
FILE_PATHS_TXT = branch-files.list

# Create a text file containing files list of files changed in the last commit
create_files_list:
	git diff --name-only HEAD..develop > $(FILE_PATHS_TXT)
	bat $(FILE_PATHS_TXT)

# Target to run PHP-CS-Fixer for all files listed in the text file
php-cs-fix-files-in-list:
	while IFS= read -r file; do \
	    vendor/bin/php-cs-fixer fix "$$file" --rules=@Symfony --using-cache=no; \
	done < $(FILE_PATHS_TXT)

console_regenerate_db_dev:
	php bin/console doctrine:cache:clear-metadata --env=dev
	php bin/console cache:clear --env=dev
	php bin/console doctrine:database:drop --env=dev --force
	php bin/console doctrine:database:create --env=dev
	php bin/console doctrine:migrations:migrate --env=dev
	
console_regenerate_db_test:
	php bin/console doctrine:cache:clear-metadata --env=test
	php bin/console cache:clear --env=test
	php bin/console doctrine:database:drop --env=test --force
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:migrations:migrate --env=test --no-interaction

install_phive:
	wget -O phive.phar https://phar.io/releases/phive.phar
	wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc
	gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79
	gpg --verify phive.phar.asc phive.phar
	chmod +x phive.phar
	sudo mv phive.phar /usr/local/bin/phive

remove_phive:
	sudo rm /usr/local/bin/phive

install_tools: install_phive
	phive install -g phpmd
	phive install -g php-cs-fixer
#	phive install -g psalm
	phive install -g phpstan
	
remove_tools: remove_phive
	phive remove phpmd
	phive remove php-cs-fixer
#	phive remove psalm
	phive remove phpstan


db_rebuild:
	php bin/console docache:clear
	php bin/console dodoctrine:database:drop --force
	php bin/console dodoctrine:database:create
	php bin/console dodoctrine:migrations:migrate
	php bin/console doctrine:schema:validate


phpstan_analyse_src:
	vendor/bin/phpstan analyse -l 6 -c phpstan.neon --memory-limit=1G --no-progress --error-format=table --no-interaction --ansi src 

coverage-html:
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html var/reports

coverage-text:
	XDEBUG_MODE=coverage php bin/phpunit --coverage-text

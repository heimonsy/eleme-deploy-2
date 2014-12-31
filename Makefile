ctags:
	ctags -R --fields=+aimS --languages=php --php-kinds=cidf --exclude=tests --exclude=composer.phar

requirements:
	@echo "\n--------------> requirements <--------------\n"
	composer install

deploy: requirements

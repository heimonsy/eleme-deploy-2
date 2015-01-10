ctags:
	ctags -R --fields=+aimS --languages=php --php-kinds=cidf --exclude=tests --exclude=composer.phar

requirements:
	@echo "\n--------------> requirements <--------------\n"
	composer install

complie:
	@echo "\n--------------> complie <--------------\n"
	jsx public/packages/src/js/ public/packages/dist/js/

watch:
	jsx --watch public/packages/src/js/ public/packages/dist/js/

deploy: requirements complie

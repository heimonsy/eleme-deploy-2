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

rsync-dev:
	ssh dev-deploy "sudo chown guoziqian -R /var/www/deploy2"
	rsync -az --force --delete --delay-updates -e "ssh -o ConnectTimeout=30 -p 2014" --exclude-from=./rsync_exclude.conf ./ guoziqian@dev-deploy:/var/www/deploy2/
	ssh dev-deploy "sudo chown www-data -R /var/www/deploy2"
	ssh dev-deploy "sudo service php5-fpm restart "


deploy: requirements complie

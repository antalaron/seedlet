ifndef APP_ENV
	include .env
	include .env.local
endif

ifndef PHP_EXECUTABLE
	CONSOLE?=bin/console
	COMPOSER?=composer
	PHPUNIT?=bin/phpunit
else
	CONSOLE?=$(PHP_EXECUTABLE) bin/console
	COMPOSER?=$(PHP_EXECUTABLE) `which composer`
	PHPUNIT?=$(PHP_EXECUTABLE) bin/phpunit
endif
YARN?=yarn

.DEFAULT_GOAL := help
.PHONY: help deploy start clear clean cc cc-test watch asset asset-prod deps perm assets-mtime autoload-optimize test tu tf test-ci check php-cs-test php-cs-test-fix js-cs-test js-cs-test-fix vendor composer.lock node_modules yarn.lock public/build/manifest.json var/.assets-mtime .env.local

help:
	@printf "\033[0;32m"
	@grep -F -h "#!" $(MAKEFILE_LIST) | grep -F -v fgrep | sed -e 's/\\$$//' | sed -e 's/#!//'
	@printf "\033[00m"
	@grep -F -h "##" $(MAKEFILE_LIST) | grep -F -v fgrep | sed -e 's/\\$$//' | grep -v '###>' | grep -v '###<' | sed -e 's/##//'

#!
#! Seedlet
#!

##
## Project setup
##---------------------------------------------------------------------------

deploy:         ## Install and start the project
deploy: deps asset-prod cc autoload-optimize perm

start:          ## Install and start the project in dev
start: deps asset cc perm

clear:          ## Remove all the cache, the logs, the sessions
clear: perm
	rm -rf var/cache/*
	rm -rf var/sessions/*
	rm -rf var/log/*
	rm -rf var/.assets-mtime
	rm -rf public/build/

clean:          ## Clear and remove dependencies
clean: clear
	rm -rf vendor/
	rm -rf node_modules/

cc:             ## Clear the cache in dev env
cc:
	$(CONSOLE) cache:pool:prune
	$(CONSOLE) cache:clear

cc-test:
	APP_ENV=test $(CONSOLE) cache:clear




##
## Assets
##---------------------------------------------------------------------------

watch:          ## Watch the assets and build their development version on change
watch: node_modules
	$(YARN) watch

asset:          ## Build the development version of the assets
asset: assets-mtime node_modules yarn.lock public/build/manifest.json

asset-prod:     ## Build the production version of the assets
asset-prod: assets-mtime public/build/manifest.json




##
## Dependencies
##---------------------------------------------------------------------------

deps:           ## Install the project PHP dependencies
deps: vendor public/build/manifest.json




##
## Tests
##---------------------------------------------------------------------------

test:           ## Run the PHP tests
test: tu tf

tu:             ## Run phpunit unit tests
tu: vendor
	$(PHPUNIT) --exclude-group functional $(FILE_TO_TEST)

tf:             ## Run phpunit functional tests
tf: deps cc-test
	$(PHPUNIT) --group functional $(FILE_TO_TEST)

test-ci:        ## Run the PHP tests in one
test-ci: deps cc-test
	if [ "${GITHUB_ACTIONS}" = "true" ]; then $(PHPUNIT) --log-junit report.xml; else $(PHPUNIT); fi




##
## Code quality checks
##---------------------------------------------------------------------------

check:          ## Run all code quality checks
check: php-cs-test js-cs-test

php-cs-test:
	mkdir -p tools/php-cs-fixer
	$(COMPOSER) require --working-dir=tools/php-cs-fixer --dev friendsofphp/php-cs-fixer:3.95.11 || true
	rm -rf config/reference.php # php-cs-fixer will try to fix this file, but it is auto-generated and should not be modified
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --diff --dry-run -vvv --config=.php-cs-fixer.dist.php
	rm -rf tools

php-cs-test-fix:
	mkdir -p tools/php-cs-fixer
	$(COMPOSER) require --working-dir=tools/php-cs-fixer --dev friendsofphp/php-cs-fixer:3.95.11 || true
	rm -rf config/reference.php # php-cs-fixer will try to fix this file, but it is auto-generated and should not be modified
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
	rm -rf tools

js-cs-test: deps
	$(YARN) semistandard

js-cs-test-fix: deps
	$(YARN) semistandard --fix




##
# Internal rules

perm:
	-setfacl -dR -m u:www-data:rwX -m u:`whoami`:rwX var 2>/dev/null
	-setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX var 2>/dev/null

assets-mtime:
	bin/assets-mtime.sh

autoload-optimize:
	if [ "${APP_ENV}" = "prod" ]; then $(COMPOSER) dump-autoload -o --no-dev; fi




# Rules from files

vendor: composer.lock
	if [ "${APP_ENV}" = "prod" ]; then $(COMPOSER) install --no-dev --no-plugins; else $(COMPOSER) install; fi

composer.lock: composer.json
	@echo composer.lock is not up to date.

node_modules: yarn.lock
	@echo node_modules/ is not up to date.
	$(YARN) install

yarn.lock: package.json
	@echo yarn.lock is not up to date.
	$(YARN) install

public/build/manifest.json: var/.assets-mtime node_modules yarn.lock
	if [ "${APP_ENV}" = "prod" ]; then $(YARN) build; else $(YARN) dev; fi

var/.assets-mtime:
	@echo Calculating assets time
	bin/assets-mtime.sh

.env.local:
	@echo Please edit .env.local file
	touch .env.local

-include .env
export

# setup for docker-compose-ci build directory
# delete "build" directory to update docker-compose-ci

ifeq (,$(wildcard ./build/Makefile))
    $(shell git submodule update --init --remote)
endif

EXTENSION=EditWarning

# docker images
MW_VERSION?=1.43
PHP_VERSION?=8.3
DB_TYPE?=mysql
DB_IMAGE?="mysql:8"

# extensions

# PageForms
# EditWarning integrates with PageForms edit forms, so CI installs it as a
# dependency to verify compatibility. Use gesinn.it's own fork/variant,
# pinned to the latest release tag rather than tracking master.
PF_REPO?=gesinn-it/mediawiki-extensions-PageForms
PF_VERSION?=2.1.9

# composer
# Enables "composer update" inside of extension
COMPOSER_EXT?=true

# nodejs
# Enables node.js related tests and "npm install"
# NODE_JS?=true

# check for build dir and git submodule init if it does not exist
include build/Makefile

.PHONY: composer-phan
composer-phan: .init ## Run Phan static analysis
ifdef COMPOSER_EXT
	$(compose-exec-wiki) bash -c "cd $(EXTENSION_FOLDER) && composer phan $(COMPOSER_PARAMS)"
endif

.PHONY: composer-phan-update-baseline
composer-phan-update-baseline: .init ## Re-generate baseline and fix indentation for PHPCS
ifdef COMPOSER_EXT
	$(compose-exec-wiki) bash -c "cd $(EXTENSION_FOLDER) && composer phan -- --save-baseline=.phan/baseline.php || true"
	$(compose) cp wiki:$(EXTENSION_FOLDER)/.phan/baseline.php .phan/baseline.php
	unexpand --first-only -t 4 .phan/baseline.php > /tmp/baseline.php && mv /tmp/baseline.php .phan/baseline.php
	$(compose) cp .phan/baseline.php wiki:$(EXTENSION_FOLDER)/.phan/baseline.php
endif

.DEFAULT_GOAL := help

PHP ?= php
COMPOSER_BIN ?= $(PHP) $(shell command -v composer)
PHP_VERSION := $(shell $(PHP) -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')
PHPUNIT := SYMFONY_PHPUNIT_DIR=$(CURDIR)/vendor/bin/.phpunit-$(PHP_VERSION) $(PHP) vendor/bin/simple-phpunit
SYMFONY ?= 8.2.*
FLEX_HOME := $(CURDIR)/var/composer-flex
COMPOSER_CACHE := $(shell $(COMPOSER_BIN) config --global cache-dir 2>/dev/null)

.PHONY: help
help: ## Liste les cibles disponibles
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-14s\033[0m %s\n", $$1, $$2}'

.PHONY: install
install: ## Installe les dépendances
	$(COMPOSER_BIN) install

.PHONY: update
update: ## Met à jour les dépendances sans contrainte de version Symfony
	$(COMPOSER_BIN) update --prefer-dist

.PHONY: deps-symfony
deps-symfony: ## Installe les dépendances épinglées sur SYMFONY=8.2.* (via Flex, comme la CI)
	COMPOSER_HOME=$(FLEX_HOME) $(COMPOSER_BIN) global config --no-plugins allow-plugins.symfony/flex true
	COMPOSER_HOME=$(FLEX_HOME) COMPOSER_CACHE_DIR=$(COMPOSER_CACHE) $(COMPOSER_BIN) global require --no-interaction --quiet symfony/flex
	COMPOSER_HOME=$(FLEX_HOME) COMPOSER_CACHE_DIR=$(COMPOSER_CACHE) SYMFONY_REQUIRE='$(SYMFONY)' $(COMPOSER_BIN) update --prefer-dist --no-interaction

.PHONY: test
test: ## Lance la suite PHPUnit
	$(PHPUNIT)

.PHONY: test-filter
test-filter: ## Lance les tests correspondant à FILTER=…
	$(PHPUNIT) --filter='$(FILTER)'

.PHONY: test-symfony
test-symfony: deps-symfony test ## Installe SYMFONY=8.2.* puis lance la suite (PHP=php8.1 pour changer d'interpréteur)

.PHONY: cs
cs: ## Vérifie le style et les en-têtes de fichier (php-cs-fixer, dry-run)
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

.PHONY: phpcsfix
phpcsfix: ## Corrige le style et les en-têtes de fichier (php-cs-fixer), imports inutilisés compris
	$(PHP) vendor/bin/php-cs-fixer fix

.PHONY: rector
rector: ## Applique Rector
	vendor/bin/rector process

.PHONY: rector-dry
rector-dry: ## Prévisualise Rector (la CI échoue sur un diff)
	vendor/bin/rector process --dry-run

.PHONY: quality
quality: cs rector-dry ## php-cs-fixer + Rector en dry-run, comme la CI coding-standards

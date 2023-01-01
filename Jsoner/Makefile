# Install composer
devenv: _composer ## Install PHP dependencies
	@composer install

test: devenv ## Run PHP tests
	@composer test

fix: devenv ## Fix coding standard violations / Format the code
	@phpcbf || true

clean: ## Remove installed PHP and JS dependencies
	@rm -rf vendor node_modules
	@rm -f composer

update: _composer ## Update PHP dependencies (from composer.json)
	@composer update

tail: ## Watch the apache error log
	@sudo multitail --mergeall -ci green /var/log/apache2/access.log -ci red /var/log/apache2/error.log

# Requires 'sudo gem install guard guard-phpunit2'
guard:
	guard

prodenv: _composer ## Install PHP dependencies without development dependencies
	@composer install --no-dev --optimize-autoloader

_composer:
    @wget --quiet https://getcomposer.org/composer.phar -O composer

# See http://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
.PHONY: help
.DEFAULT_GOAL := help
help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
	    awk 'BEGIN { \
	            FS = ":.*?## "; \
	            printf "  _   _   _   _   _   _  \n / \\ / \\ / \\ / \\ / \\ / \\ \n( j | s | o | n | e | r )\n \\_/ \\_/ \\_/ \\_/ \\_/ \\_/\n"; \
	            printf "  Yes, I am a Makefile.\n\n"; \
	            printf "\033[0;33m%s\033[0m\n", "Available targets:" \
	        }; { \
	            printf "  \033[0;32m%-15s\033[0m %s\n", $$1, $$2 \
        }'
    #                                 ^^ 1. column width

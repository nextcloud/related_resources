# SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
app_name=Related_Resources

build_dir=$(CURDIR)/build/artifacts
sign_dir=$(build_dir)/sign
package_name=$(shell echo $(app_name) | tr '[:upper:]' '[:lower:]')
version=1.1.0-alpha1

all: release

appstore: release

npm-init:
	npm install

npm-update:
	npm update

 # Building
build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

clean-js:
	rm -rf js

clean-dev:
	rm -rf node_modules

cs-check: composer-dev
	composer cs:check

cs-fix: composer-dev
	composer cs:fix

clean:
	rm -rf $(build_dir)
	rm -rf node_modules

# composer packages
composer:
	composer install --prefer-dist --no-dev
	composer upgrade --prefer-dist --no-dev

composer-dev:
	composer install --prefer-dist --dev
	composer upgrade --prefer-dist --dev

js: clean-js npm-init build-js-production

release: clean composer js
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/translationfiles \
	--exclude=/.tx \
	--exclude=/tests \
	--exclude=.git \
	--exclude=/.github \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=/README.md \
	--exclude=/composer.json \
	--exclude=/testConfiguration.json \
	--exclude=node_modules \
	--exclude=/composer.lock \
	--exclude=/.gitattributes \
	--exclude=/.gitignore \
	--exclude=/.scrutinizer.yml \
	--exclude=/.travis.yml \
	--exclude=/Makefile \
	./ $(sign_dir)/$(package_name)
	tar -czf $(build_dir)/$(package_name)-$(version).tar.gz \
		-C $(sign_dir) $(package_name)

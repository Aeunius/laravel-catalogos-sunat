# Todo corre en Docker con la imagen oficial composer:2 (PHP 8.5): no hace falta
# PHP en el equipo ni _infra. Las demás versiones de PHP y Laravel las prueba el CI.
#
# La caché de Composer queda en ~/.cache/composer, compartida entre ejecuciones.

TTY   := $(shell [ -t 0 ] && echo -t)
CACHE := $(HOME)/.cache/composer
RUN    = docker run --rm -i $(TTY) -u $$(id -u):$$(id -g) \
         -v $(CURDIR):/app -v $(CACHE):/tmp/cache -e COMPOSER_CACHE_DIR=/tmp/cache \
         -w /app composer:2

.PHONY: help install update test analyse format lint composer shell

help:           ## Lista los comandos
	@grep -hE '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  make %-10s %s\n", $$1, $$2}'

$(CACHE):
	@mkdir -p $@

install: | $(CACHE) ## Instala las dependencias
	$(RUN) composer install

update: | $(CACHE) ## Actualiza las dependencias
	$(RUN) composer update

test:           ## Corre Pest (make test a="--filter=ruc")
	$(RUN) vendor/bin/pest $(a)

analyse:        ## PHPStan
	$(RUN) vendor/bin/phpstan analyse --memory-limit=1G

format:         ## Formatea con Pint
	$(RUN) vendor/bin/pint

lint:           ## Revisa el formato sin cambiar nada (como el CI)
	$(RUN) vendor/bin/pint --test

composer: | $(CACHE) ## make composer c="require paquete"
	$(RUN) composer $(c)

shell:          ## sh dentro del contenedor
	$(RUN) sh

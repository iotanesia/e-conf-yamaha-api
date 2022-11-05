Phony: help coverage test build-container build-instance build-all

# COLORS
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)


TARGET_MAX_CHAR_NUM=20
## Show Help make command
help:
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk '/^[a-zA-Z\-\_0-9]+:/ { \
                helpMessage = match(lastLine, /^## (.*)/); \
                if (helpMessage) { \
                        helpCommand = substr($$1, 0, index($$1, ":")-1); \
                        helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
                        printf "  ${YELLOW}%-$(TARGET_MAX_CHAR_NUM)s${RESET} ${GREEN}%s${RESET}\n", helpCommand, helpMessage; \
                } \
        } \
        { lastLine = $$0 }' $(MAKEFILE_LIST)

## Running Unit-test Code
test:
	@echo "Step Unit Test"

## Running Code Coverage
coverage:
	@echo "Step Coverage Code"

## Running Code Dependency Check
check:
	@echo "Step Check Code"

## Build Code to Binary Artifact
build:
	composer install
	php artisan key:generate
	php artisan storage:link
	mkdir $(CI_PROJECT_DIR)/$(ARTIFACT_DIR)
	tar -czvf $(ARTIFACT_DIR)/$(CI_PROJECT_NAME).tar.gz --exclude=$(ARTIFACT_DIR) --exclude=.git --exclude=.gitignore --exclude=.gitlab-ci.yml --exclude=Dockerfiles --exclude=docker-compose .


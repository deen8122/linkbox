SAIL := ./vendor/bin/sail

.PHONY: init up seed storage-link npm-install

init: up seed storage-link npm-install
	@echo ""
	@echo "Готово. Запусти в отдельном терминале:"
	@echo "  ./vendor/bin/sail npm run dev"

up:
	$(SAIL) up -d
	@echo "Жду поднятия MySQL..."
	@sleep 5

seed:
	$(SAIL) artisan migrate:fresh --seed

storage-link:
	$(SAIL) artisan storage:link

npm-install:
	$(SAIL) npm install

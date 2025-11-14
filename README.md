# Сервис бронирования слотов


# Установка и запуск


1. Установить параметры соединения с базой данных **mysql** в файле **.env**
2. ```php artisan migrate:fresh --seed```
3. ```php artisan serve```


# Примеры curl запросов

**Получение доступных слотов:**

```curl http://localhost:8000/slots/availability```

**Создание холда, повтор с тем же ключом:**

```curl -X POST http://localhost:8000/slots/1/hold -H 'Idempotency-Key: eff3db47-0d88-4597-a473-e9ecfce59437'```

**Подтверждение холда:**

```curl -X POST http://localhost:8000/holds/1/confirm```

**Отмена холда:**

```curl -X DELETE http://localhost:8000/holds/1```

**Конфликт при оверселе:**

```curl -X POST http://localhost:8000/slots/2/hold -H 'Idempotency-Key: eff3db47-0d88-4597-a473-e9ecfce59437'```  
```curl -X POST http://localhost:8000/holds/2/confirm```

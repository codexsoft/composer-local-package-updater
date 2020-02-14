# CodexSoft Composer Local Package Updater

Helper для быстрого получения composer-пакетов из локальных репозиториев. Полезен когда в проекте используется зависимость в виде вашего composer-пакета, который находится в разработке и развернут локально.

Не изменяет `composer.json` и `composer.lock` проекта.

- Сделает composer install

- Скопирует composer.json в composer.local.json, заменив/добавив в required пакеты с указанными версиями, и прописав репозитории типа path на локальные директории

- Скопирует composer.lock в composer.local.lock

- Сделает composer update <package-name-1> <package-name-2> ... <package-name-N>

Чтобы восстановить в /vendor оригинальные пакеты, просто делаем composer install и работаем как обычно.

## Как использовать

Создадим новый файл с php-скриптом (например, /local.php) и заполним его следующим содержимым:

```php
<?php

use CodexSoft\ComposerLocalPackages\Updater;

require __DIR__.'/vendor/autoload.php';

(new Updater())
    ->add('vendor/package-name', 'dev-feature/awesome', '/path/to/package/repo')
    ->run();
```

Поддерживается добавление/замена версии множества пакетов, а также некоторые другие опции:

```php
(new Updater())
    ->add('vendor/package-name1', 'dev-feature/first', '/path/to/package/repo1')
    ->add('vendor/package-name2', 'dev-feature/second', '/path/to/package/repo2')
    ->setComposerCommand('/bin/composer')
    ->setComposerOptions('--no-scripts -vvv')
    ->setMergeConfig([
        'repositories' => [
            ['packagist.org' => false]
        ],
    ])
    ->run();
```

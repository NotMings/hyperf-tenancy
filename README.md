# component-creator

```
composer create-project hyperf/component-creator
```

## Tests

- Run test suite:

```
composer test
```

- Generate coverage (requires Xdebug):

```
XDEBUG_MODE=coverage ./vendor/bin/phpunit -c phpunit.xml --coverage-html coverage --coverage-clover coverage/clover.xml
```

Coverage reports will be written to the `coverage/` directory.

- Show coverage directly in terminal (text summary):

```
composer coverage-text
```

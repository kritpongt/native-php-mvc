# Architecture breakdown

```bash
composer init
```
```bash
composer require twig/twig
composer require --dev phpunit/phpunit
```

## Edit composer.json
```json
{
	"autoload": {
		"psr-4": {
			"Core\\": "core/",
			"App\\": "app/",
		}
	},
	"autoload-dev": {
		"psr-4": {
			"Tests\\": "tests/"
		}
	},
	"scripts": {
		"test": "phpunit",
		"test:unit": "phpunit --testsuite Unit",
		"test:integration": "phpunit --testsuite Integration"
	}
}
```
and then
```bash
composer dump-autoload
```

```bash
npm init -y

npm install -D tailwindcss @tailwindcss/cli
```

## Edit package.json
```json
{
  "private": true,
  "scripts": {
    "dev": "tailwindcss -i resources/css/app-tailwind.css -o public/assets/app-tailwind.css --watch",
    "build": "tailwindcss -i resources/css/app-tailwind.css -o public/assets/app-tailwind.css --minify"
  },
}
```

#	Flow 
main
- core/Env
- core/Config
- core/Request
- core/Response
- core/Router
- core/Container
- core/Database
- core/Session
- core/Csrf
- core/Middleware
- core/Pipeline
- core/View
- core/Migrator

extension
- core/Logger
- core/ErrorHandler
- core/Translator
- core/Url
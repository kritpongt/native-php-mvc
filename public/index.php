<?php
declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Middlewares\HandleErrors;
use App\Middlewares\ResolveLocale;
use App\Middlewares\SecurityHeaders;
use App\Middlewares\ShareViewData;
use App\Middlewares\StartSession;
use App\Middlewares\VerifyCsrf;
use Core\ClientContext;
use Core\ClientContextInterface;
use Core\Config;
use Core\Container;
use Core\Csrf;
use Core\Database;
use Core\Env;
use Core\ErrorHandler;
use Core\Logger;
use Core\LoggerInterface;
use Core\Pipeline;
use Core\Request;
use Core\Response;
use Core\Router;
use Core\Session;
use Core\SessionInterface;
use Core\Translator;
use Core\Twig\TwigI18nExtension;
use Core\Url;
use Core\View;

$root = dirname(__DIR__);

// ## load .env into $_ENV
Env::load($root.'/.env');

$config = new Config($root.'/config');

// ## setup timezone
$tz = (string) $config->get('app.timezone', 'Asia/Bangkok');
if(!date_default_timezone_set($tz)){
	throw new RuntimeException("Invalid app.timezone: {$tz}");
}

$logger = new Logger(
	(string) $config->get('storage.logs'),
	(int) $config->get('storage.log_retention_days', 14),
	(string) $config->get('storage.log_min_level', 'WARNING')
);
$errorHandler = new ErrorHandler($logger, (bool) $config->get('app.debug'));
$errorHandler->register();

$container = new Container();

// ## Config is already built - register the instance so everyone shares it
$container->instance(Config::class, $config);
$container->instance(LoggerInterface::class, $logger);
$container->instance(ErrorHandler::class, $errorHandler);

$container->singleton(Database::class, static function(Container $c): Database{
	return new Database($c->get(Config::class));
});

$container->singleton(SessionInterface::class, static function(Container $c): SessionInterface{
	return $c->get(Session::class);
});

$container->singleton(Translator::class, static function(Container $c): Translator{
	return new Translator($c->get(Config::class));
});

$container->singleton(Url::class, static function(Container $c): Url{
	return new Url($c->get(Translator::class));
});

$container->singleton(View::class, static function(Container $c): View{
	$view = new View($c->get(Config::class));

	$view->addExtension(new TwigI18nExtension(
		$c->get(Translator::class),
		$c->get(Url::class)
	));

	return $view;
});

$container->singleton(ClientContextInterface::class, static function(Container $c): ClientContextInterface{
	return new ClientContext();
});

$router = new Router($container);
(require $root.'/app/Routes/web.php')($router);
(require $root.'/app/Routes/backoffice.php')($router);

$request = Request::fromGlobals();

// ## ORDER MATTERS:
// SecurityHeaders outermost = every response gets stamped,
// HandleErrors next = catches everything inside it,
// StartSession before VerifyCsrf = token lives in the session
$global = [
	SecurityHeaders::class,
	HandleErrors::class,
	StartSession::class,
	ResolveLocale::class,
	ShareViewData::class,
	VerifyCsrf::class
];

// ## global pipeline
$response = (new Pipeline($container))->send(
	$request,
	$global,
	static fn(Request $r): Response => $router->dispatch($r)
);

$response->send();
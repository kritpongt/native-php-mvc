<?php
declare(strict_types=1);

namespace Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly bool $debug
	){}

	/** call ONCE at boot, before the pipeline runs */
	public function register(): void
	{
		// we render our own page - PHP must never print errors itself
		ini_set('display_errors', '0');
		error_reporting(E_ALL);

		// warnings/notices become exceptions -> same catch path as everything else
		set_error_handler(static function(int $severity, string $message, string $file, int $line): bool{
			// honor @-suppression: masked severity -> let PHP handle it, do not throw
			if(!(error_reporting() & $severity)){ return false; }
			throw new ErrorException($message, 0, $severity, $file, $line);
		});

		// last resort: exception thrown OUTSIDE the pipeline (bootstrap itself)
		set_exception_handler(function(Throwable $e): void{
			$this->logThrowable($e);
			http_response_code(500);
			echo $this->body($e);
		});

		// fatal errors (OOM, parse) skip every catch - grab them on shutdown
		register_shutdown_function(function(): void{
			$err = error_get_last();

			if($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)){
				$this->logger->error('FATAL: '.$err['message'], [
					'file' => $err['file'].':'.$err['line']
				]);
			}
		});
	}

	/** normal path: HandleErrors middleware calls this */
	public function render(Throwable $e): Response
	{
		$this->logThrowable($e);

		return Response::html($this->body($e), 500);
	}

	private function logThrowable(Throwable $e): void
	{
		$this->logger->error($e::class.': '.$e->getMessage(), [
			'file' => $e->getFile().':'.$e->getLine(),
			'trace' => $this->traceWithoutArgs($e)
		]);
	}

	private function body(Throwable $e): string
	{
		if(!$this->debug){
			// production: generic page, zero internals - project rule
			return '<h1>500 - Something went wrong</h1>';
		}

		// debug only: full detail, escaped so a message cannot inject HTML
		$detail = $e::class.': '.$e->getMessage()."\n\n".implode("\n", $this->traceWithoutArgs($e));

		return '<h1>500</h1><pre>'.htmlspecialchars($detail, ENT_QUOTES).'</pre>';
	}

	/**
	 * getTraceAsString() prints call ARGUMENTS - a frame through
	 * password_verify('secret...') would leak the password into the log.
	 * Build the trace ourselves, args dropped. "Never log sensitive data."
	 *
	 * @return list<string>
	 */
	private function traceWithoutArgs(Throwable $e): array
	{
		$frames = [];

		foreach($e->getTrace() as $i => $f){
			$frames[] = sprintf(
				'#%d %s%s%s() at %s:%s',
				$i,
				$f['class'] ?? '',
				$f['type'] ?? '',
				$f['function'],
				$f['file'] ?? '?',
				$f['line'] ?? '?'
			);
		}

		return $frames;
	}
}
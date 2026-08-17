<?php
declare(strict_types=1);

namespace Core\Exception;

use RuntimeException;
use Throwable;

class ContainerException extends RuntimeException
{
	private array $path = [];
	private string $originalMessage;

	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct($message, 0, $previous);

		$this->originalMessage = $message;
	}

	public function addPath(string $className): void
	{
		$this->path[] = $className;

		$lines = ["", "\tDependency Resolution Path:"];

		$reversed = array_reverse($this->path);
		foreach ($reversed as $index => $c) {
				$indent = str_repeat("  ", $index);
				$lines[] = "\t{$indent}└─ {$c}";
		}

		$this->message = $this->originalMessage . implode("\n", $lines);
	}
}
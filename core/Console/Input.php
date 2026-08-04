<?php
declare(strict_types=1);

namespace Core\Console;

final class Input
{
	public function __construct(private readonly array $argv){}

	public function getOption(string $flag): ?string
	{
		foreach($this->argv as $i => $arg){
			if($arg === $flag){
				return $this->argv[$i + 1] ?? null;
			}
			if(str_starts_with($arg, $flag.'=')){
				return substr($arg, strlen($flag) + 1);
			}
		}

		return null;
	}

	public function hasOption(string $flag): bool
	{
		foreach($this->argv as $arg){
			if($arg === $flag){ return true; }
		}

		return false;
	}
}
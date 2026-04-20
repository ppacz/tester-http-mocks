<?php declare(strict_types = 1);

namespace Mangoweb\Tester\HttpMocks;

use Nette\Http\Request;
use Nette\Http\UrlImmutable;
use Nette\Http\UrlScript;

class HttpRequest extends Request
{

	/** @var array<string, string> */
	private array $ownHeaders = [];

	/** @var array<string, true> */
	private array $removedHeaders = [];

	/** @var array<string, mixed>|null */
	private ?array $testPost = null;

	private ?UrlScript $testUrl = null;

	private ?string $testMethod = null;

	/** @var (callable(): ?string)|null */
	private $testRawBodyCallback = null;

	private ?string $testBody = null;


	public function setRawBody(?string $body): void
	{
		$this->testBody = $body;
	}


	public function setHeader(string $name, string $value): void
	{
		$name = strtolower($name);
		$this->ownHeaders[$name] = $value;
		unset($this->removedHeaders[$name]);
	}


	public function removeHeader(string $name): void
	{
		$name = strtolower($name);
		unset($this->ownHeaders[$name]);
		$this->removedHeaders[$name] = true;
	}


	public function getHeader(string $header): ?string
	{
		$header = strtolower($header);

		if (isset($this->removedHeaders[$header])) {
			return null;
		}

		if (array_key_exists($header, $this->ownHeaders)) {
			return $this->ownHeaders[$header];
		}

		return parent::getHeader($header);
	}


	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		$merged = parent::getHeaders();

		foreach ($this->removedHeaders as $name => $_) {
			unset($merged[$name]);
		}

		foreach ($this->ownHeaders as $name => $value) {
			$merged[$name] = $value;
		}

		return $merged;
	}


	/**
	 * @param array<string, mixed> $post
	 */
	public function setPost(array $post): void
	{
		$this->testPost = $post;
	}


	public function getPost(?string $key = null): mixed
	{
		if ($this->testPost === null) {
			return $key === null ? parent::getPost() : parent::getPost($key);
		}

		return $key === null ? $this->testPost : ($this->testPost[$key] ?? null);
	}


	public function setUrl(UrlScript $url): void
	{
		$this->testUrl = $url;
	}


	public function getUrl(): UrlScript
	{
		return $this->testUrl ?? parent::getUrl();
	}


	public function getQuery(?string $key = null): mixed
	{
		if ($key === null) {
			return $this->testUrl !== null
				? $this->testUrl->getQueryParameters()
				: parent::getQuery();
		}

		return $this->testUrl !== null
			? $this->testUrl->getQueryParameter($key)
			: parent::getQuery($key);
	}


	public function isSecured(): bool
	{
		return $this->getUrl()->getScheme() === 'https';
	}


	public function getReferer(): ?UrlImmutable
	{
		$referer = $this->getHeader('Referer');
		if ($referer === null) {
			return null;
		}
		try {
			return new UrlImmutable($referer);
		} catch (\Nette\InvalidArgumentException) {
			return null;
		}
	}


	public function getOrigin(): ?UrlImmutable
	{
		$header = $this->getHeader('Origin') ?? 'null';
		try {
			return $header === 'null' ? null : new UrlImmutable($header);
		} catch (\Nette\InvalidArgumentException) {
			return null;
		}
	}


	public function setMethod(string $method): void
	{
		$this->testMethod = $method;
	}


	public function getMethod(): string
	{
		return $this->testMethod ?? parent::getMethod();
	}


	public function isMethod(string $method): bool
	{
		return strcasecmp($this->getMethod(), $method) === 0;
	}


	/**
	 * @param (callable(): ?string)|null $callback
	 */
	public function setRawBodyCallback(?callable $callback): void
	{
		$this->testRawBodyCallback = $callback;
	}


	public function getRawBody(): ?string
	{
		if ($this->testRawBodyCallback !== null) {
			return ($this->testRawBodyCallback)();
		}

		return $this->testBody ?? parent::getRawBody();
	}


	public function isSameSite(): bool
	{
		return true;
	}

}

<?php
namespace App\Test;

use App\ServiceLoader;
use App\Request\Collection\CollectionEntity;
use App\Request\Collection\CollectionMode;
use App\Request\Collection\CollectionRepository;
use App\Request\Collection\PrivateCollectionRepository;
use Gt\Config\Config;
use Gt\Http\Response;
use Gt\Routing\Path\DynamicPath;
use Gt\ServiceContainer\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ServiceLoaderTest extends TestCase {
	public function testEmptyReadOnlyCollectionRedirectsToSessionExplanation():void {
		$repository = $this->createMock(CollectionRepository::class);
		$repository->method("retrieveAll")->willReturn([]);
		$response = $this->createMock(Response::class);
		$response->expects(self::once())->method("redirect")
			->with("/session-required/")
			->willThrowException(new RuntimeException("redirect completed"));
		$loader = $this->loader($repository, $response);
		$this->expectExceptionMessage("redirect completed");
		$loader->loadCollectionEntity();
	}

	public function testExistingSharedCollectionRemainsAccessible():void {
		$collection = new CollectionEntity("Shared", CollectionMode::request);
		$repository = $this->createMock(CollectionRepository::class);
		$repository->method("retrieveAll")->willReturn([$collection]);
		$response = $this->createMock(Response::class);
		$response->expects(self::never())->method("redirect");
		self::assertSame($collection, $this->loader($repository, $response)->loadCollectionEntity());
	}

	public function testOwnerCanStillCreateFirstCollection():void {
		$collection = new CollectionEntity("Collection 1", CollectionMode::request);
		$repository = $this->createMock(PrivateCollectionRepository::class);
		$repository->method("retrieveAll")->willReturn([]);
		$repository->expects(self::once())->method("create")->willReturn($collection);
		$response = $this->createMock(Response::class);
		$response->expects(self::never())->method("redirect");
		self::assertSame($collection, $this->loader($repository, $response)->loadCollectionEntity());
	}

	private function loader(CollectionRepository $repository, Response $response):ServiceLoader {
		$container = new Container();
		$container->set($repository, $response, new DynamicPath("/request/test/"));
		return new ServiceLoader(new Config(), $container);
	}
}

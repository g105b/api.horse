<?php
namespace App;

use App\Http\FetchHandler;
use App\Request\Collection\CollectionEntity;
use App\Request\Collection\CollectionMode;
use App\Request\Collection\CollectionRepository;
use App\Request\RequestEntity;
use App\Request\RequestRepository;
use App\Response\ResponseRepository;
use Gt\Http\Uri;
use Gt\Routing\Path\DynamicPath;
use Gt\Session\Session;
use Gt\WebEngine\Middleware\DefaultServiceLoader;

class ServiceLoader extends DefaultServiceLoader {
	public function loadShareId():ShareId {
		$session = $this->container->get(Session::class);
		if($shareId = $session->get(ShareId::class)) {
			return $shareId;
		}

		$shareId = new ShareId();
		$session->set(ShareId::class, $shareId);
		return $shareId;
	}

	public function loadCollectionRepository():CollectionRepository {
		$shareId = $this->container->get(ShareId::class);
		$uri = $this->container->get(Uri::class);
		$mode = CollectionMode::fromUri($uri);
		return new CollectionRepository("data/$shareId", $mode);
	}

	public function loadCollectionEntity():CollectionEntity {
		$dynamicPath = $this->container->get(DynamicPath::class);
		$collectionRepository = $this->container->get(CollectionRepository::class);

		if($id = $dynamicPath->get("collection-id")) {
			$collection = $collectionRepository->retrieve($id);
			if($collection) {
				return $collection;
			}
		}

		// if user has no collections, create one
		$allCollectionList = $collectionRepository->retrieveAll();
		if(!$allCollectionList) {
			return $collectionRepository->create();
		}

		return $allCollectionList[0];
	}

	public function loadRequestRepository():RequestRepository {
		$shareId = $this->container->get(ShareId::class);
		$collectionEntity = $this->container->get(CollectionEntity::class);

		return new RequestRepository(
			"data/$shareId/{$collectionEntity->mode->name}/$collectionEntity->id",
		);
	}

	public function loadRequestEntity():?RequestEntity {
		$dynamicPath = $this->container->get(DynamicPath::class);
		$requestRepository = $this->container->get(RequestRepository::class);

		$id = $dynamicPath->get("request-id");
		if($id === "_new") {
			return null;
		}

		return $requestRepository->retrieve($id);
	}

	public function loadResponseRepository():ResponseRepository {
		$shareId = $this->container->get(ShareId::class);
		$collectionEntity = $this->container->get(CollectionEntity::class);

		return new ResponseRepository(
			"data/$shareId/{$collectionEntity->mode->name}/$collectionEntity->id",
		);
	}

	public function loadFetchHandler():FetchHandler {
		return new FetchHandler();
	}
}

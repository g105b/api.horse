<?php
namespace App;

use App\Http\FetchHandler;
use App\Request\Collection\CollectionEntity;
use App\Request\Collection\CollectionMode;
use App\Request\Collection\CollectionRepository;
use App\Request\Collection\PrivateCollectionRepository;
use App\Request\PrivateRequestRepository;
use App\Request\RequestEntity;
use App\Request\RequestRepository;
use App\Request\SecretRepository;
use App\Response\ResponseRepository;
use Gt\Http\Uri;
use Gt\Routing\Path\DynamicPath;
use Gt\Session\Session;
use Gt\WebEngine\Middleware\DefaultServiceLoader;

class ServiceLoader extends DefaultServiceLoader {
	public function loadShareId():ShareId {
		$dynamicPath = $this->container->get(DynamicPath::class);
		if($shareIdString = $dynamicPath->get("share-id")) {
			$shareId = new ShareId();
			$shareId->id = $shareIdString;
			return $shareId;
		}

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
		$dir = "data/$shareId";
		$mode = CollectionMode::fromUri($uri);

		$dynamicPath = $this->container->get(DynamicPath::class);
		if($urlShareId = $dynamicPath->get("share-id")) {
			$session = $this->container->get(Session::class);
			if($shareIdSession = $session->get(ShareId::class)) {
				if($urlShareId === (string)$shareIdSession) {
					return new PrivateCollectionRepository($dir, $mode);
				}
			}
		}

		return new CollectionRepository($dir, $mode);
	}

	public function loadCollectionEntity():CollectionEntity {
		$dynamicPath = $this->container->get(DynamicPath::class);
		$collectionRepository = $this->container->get(CollectionRepository::class);

		if($id = $dynamicPath->get("collection-id")) {
			if($collection = $collectionRepository->retrieve($id)) {
				return $collection;
			}
		}
		else {
			if($collection = $collectionRepository->getCurrent()) {
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
		$collectionRepository = $this->container->get(CollectionRepository::class);
		$collectionEntity = $this->container->get(CollectionEntity::class);

		if($collectionRepository instanceof PrivateCollectionRepository) {
			return new PrivateRequestRepository(
				"data/$shareId/{$collectionEntity->mode->name}/$collectionEntity->id",
			);
		}

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

	public function loadSecretRepository():SecretRepository {
		$shareId = $this->container->get(ShareId::class);
		$collectionEntity = $this->container->get(CollectionEntity::class);

		return new SecretRepository(
			"data/$shareId/{$collectionEntity->mode->name}/$collectionEntity->id",
		);
	}

	public function loadFetchHandler():FetchHandler {
		return new FetchHandler();
	}
}

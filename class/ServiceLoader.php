<?php
namespace App;

use App\Collection\CollectionEntity;
use App\Collection\CollectionMode;
use App\Collection\CollectionRepository;
use App\Http\FetchHandler;
use App\Request\RequestEntity;
use App\Request\RequestRepository;
use App\Response\ResponseRepository;
use Gt\Http\Header\RequestHeaders;
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

	public function loadCollection():CollectionEntity {
		$dynamicPath = $this->container->get(DynamicPath::class);
		$collectionRepository = $this->container->get(CollectionRepository::class);

		if($id = $dynamicPath->get("collection")) {
			return $collectionRepository->retrieve($id, false);
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
			"data/$shareId/$collectionEntity->id/{$collectionEntity->mode->name}",
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
		return new ResponseRepository(
			"data/request",
			$this->container->get(ShareId::class),
		);
	}

	public function loadFetchHandler():FetchHandler {
		return new FetchHandler();
	}
}

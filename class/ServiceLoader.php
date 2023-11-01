<?php
namespace App;

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

	public function loadRequestEntity():?RequestEntity {
		$dynamicPath = $this->container->get(DynamicPath::class);
		$requestRepository = $this->container->get(RequestRepository::class);

		$id = $dynamicPath->get("request-id");
		if($id === "_new") {
			return null;
		}

		return $requestRepository->retrieve($id);
	}

	public function loadRequestRepository():RequestRepository {
		return new RequestRepository(
			"data/request",
			$this->container->get(ShareId::class),
		);
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

<?php
namespace App;

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
}

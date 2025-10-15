<?php
namespace Oacs\Storage;

use Zend\Authentication\Storage;

class OacsAuthStorage extends Storage\Session {
	public function getSessionManager() { return $this->session->getManager(); }
	public function getSessionId() { return $this->session->getManager()->getId(); }
}
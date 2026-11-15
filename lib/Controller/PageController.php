<?php

namespace OCA\Money\Controller;

use OCP\IRequest;
use OCP\Util;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class PageController extends Controller {

  public function __construct(
    string $AppName,
    IRequest $request
  ) {
    parent::__construct($AppName, $request);
  }

  #[NoAdminRequired]
  #[NoCSRFRequired]
  public function index() {
    Util::addScript($this->appName, 'money-main.iife');
    Util::addStyle($this->appName, 'money-main');

    $response = new TemplateResponse($this->appName, 'main');

    return $response;
  }

}

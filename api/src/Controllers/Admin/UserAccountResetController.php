<?php

namespace Api\Controllers\Admin;

use Api\Models\Admin\UserAccountResetModel;
use Api\Utils\BaseController;
use Api\Utils\Exceptions\Auth\AdminException;

class UserAccountResetController extends BaseController
{
  private $model;
  private $module = "admin";
  private $sse_event = "admin/users";

  public function __construct(
    private ?string $uid,
  ) {
    parent::__construct();

    if ($this->user->is_admin === false) {
      throw new AdminException();
    }

    $this->model = new UserAccountResetModel(admin: $this->user);
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, PUT");
        break;

      case 'PUT':
        $this->update($this->uid);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, PUT");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Réinitialise un compte utilisateur.
   * 
   * @param string $uid UID du compte à réinitialiser.
   */
  public function update(string $uid)
  {
    if (!$this->model->read($uid)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $donnees = $this->model->update($uid, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);

    notify_sse($this->sse_event, __FUNCTION__, $uid, $donnees);
  }
}

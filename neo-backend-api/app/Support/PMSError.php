<?php

namespace App\Support;

use Error;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PMSError extends Error implements HttpExceptionInterface {

  private $tag;

  public function __construct($error)
  {
    $msg = $error instanceof XmlElement ? (string)$error : $error['Message'];
    $code = intval($error['Code'] ?? 0);
    parent::__construct($msg, $code);
    $this->tag = html_entity_decode((string)($error['Tag'] ?? ''));
  }

  public function getTag()
  {
    return $this->tag ?? '';
  }

  public function getStatusCode()
  {
    return 409; // Conflict
  }

  public function getHeaders()
  {
    return [];
  }
}

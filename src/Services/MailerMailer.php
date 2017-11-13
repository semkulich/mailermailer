<?php

namespace Drupal\mailermailer\Services;

use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use MAILAPI_Client;
use MAILAPI_Error;

use Psr\Log\LoggerInterface;

/**
 * Provides a MailerMailer third-party integration.
 */
class MailerMailer {

  use StringTranslationTrait;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal State storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal DB logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  // Define errors for user feedback
  protected $user_errors = [
    '112'   => 'A status error has occurred.',
    '301'   => 'Missing required data.',
    '302'   => 'Invalid data detected. Check that your email address is properly formatted.',
    '304'   => 'Duplicate data detected. Your email address is already signed up for this list.',
    '10002' => 'Disallowed email address.',
    '11003' => 'Malformed or invalid API key.',
    '12001' => 'The owner of this account has been suspended.',
    '12002' => 'The owner of this account has been termianted.',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $httpClient,
                              StateInterface $state,
                              LoggerInterface $logger,
                              ConfigFactoryInterface $config_factory) {
    $this->httpClient = $httpClient;
    $this->state = $state;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * Register API key.
   *
   * @return string
   *   Response.
   */
  public function getApiKey() {
    return $this->configFactory->get('mailermailer.settings')->get('mailermailer_api_key');
  }

  /**
   * Register API key.
   *
   * @return mixed
   *   Response.
   */
  public function getMailerApi() {
    $api_key = $this->getApiKey();
    $mail_api = new MAILAPI_Client($api_key);
    $ping = $mail_api->ping();
    $response = $this->evaluateResponse($ping);
    if ($response == 1) {
      return $mail_api;
    }
  }

  /**
   * Get form fields.
   *
   * @return array
   *   Form fields.
   */
  public function getFormFields() {
    if ($mailer_api = $this->getMailerApi()) {
      return $mailer_api->getFormFields();
    }
  }

  /**
   * Add new member.
   *
   * @param string $member
   *   Member mail.
   *
   * @return mixed
   *   Response.
   */
  public function addMember($member) {
    if ($mailer_api = $this->getMailerApi()) {
      return $mailer_api->addMember($member);
    }
  }

  /**
   * Add bulk members.
   *
   * @param array $members
   *   Member mail.
   *
   * @return mixed
   *   Response.
   */
  public function addBulkMembers(array $members) {
    if ($mailer_api = $this->getMailerApi()) {
      return $mailer_api->addBulkMembers($members);
    }
  }

  /**
   * Get member.
   *
   * @param string $member
   *   Member mail.
   *
   * @return mixed
   *   Response.
   */
  public function getMember($member) {
    if ($mailer_api = $this->getMailerApi()) {
      return $mailer_api->getMember($member);
    }
  }

  /**
   * Get bulk members.
   *
   * @return mixed
   *   Result.
   */
  public function getBulkMembers() {
    if ($mailer_api = $this->getMailerApi()) {
      // Get bulk members who signed up in the past year.
      $date_after = date('Y-m-d H:i:s', time() - (3 * 24 * 60 * 60));
      $date_before = date('Y-m-d H:i:s', time());
      return $mailer_api->getBulkMembers(10, 0, $date_after, $date_before, NULL, NULL, NULL, NULL);
    }
  }

  /**
   * Evaluate response.
   *
   * @param object $response
   *   Response object.
   *
   * @return mixed
   *   Response.
   */
  public function evaluateResponse($response) {
    if (MAILAPI_Error::isError($response)) {
      $error_message = "An error occurred.";
      if (array_key_exists($response->getErrorCode(), $this->user_errors)) {
        $error_message = $this->user_errors[$response->getErrorCode()];
      }
      $this->logger->error($error_message);
      return $error_message;
    }
    return $response;
  }

}

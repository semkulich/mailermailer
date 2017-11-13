<?php

namespace Drupal\mailermailer_webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\mailermailer\Services\MailerMailer;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "mailermailer_webformform_handler",
 *   label = @Translation("MailerMailer"),
 *   category = @Translation("Webform Handler"),
 *   description = @Translation("MailerMailer extra with form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class MailerMailerWebformHandler extends WebformHandlerBase {

  /**
   * The mailermailer service.
   *
   * @var \Drupal\mailermailer\Services\MailerMailer
   */
  protected $mailerMailer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, MailerMailer $mailerMailer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->mailerMailer = $mailerMailer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('mailermailer.services'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform_submission.conditions_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    parent::validateForm($form, $form_state, $webform_submission);
    // Add new member to MailerMailer list.
    $email = NULL;
    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && $element['#type'] == 'email') {
        $email = $webform_submission->getElementData($key);
        if ($email) {
          $member['user_email'] = $email;
          // We add member here since API does not allow to validate emails.
          $status = $this->mailerMailer->addMember($member);
          if (!$status) {
            $form_state->setError($form['elements'][$key], $this->t('This email is already signed up'));
          }
        }
      }
    }

  }

}

<?php
/**
 * @file
 * Contains \Drupal\mailermailer\Plugin\Block\MailerMailerBlock.
 */

namespace Drupal\mailermailer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mailermailer\Services\MailerMailer;

/**
 *
 * @Block(
 *   id = "mailermailer_block",
 *   admin_label = @Translation("MailerMailer Block"),
 *   category = @Translation("MailerMailer")
 * )
 */
class MailerMailerBlock extends BlockBase  implements ContainerFactoryPluginInterface {

  /**
   * Drupal\mailermailer\Services\MailerMailer definition.
   *
   * @var \Drupal\mailermailer\Services\MailerMailer
   */
  protected $mailermailer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailerMailer $mailermailer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailermailer = $mailermailer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('mailermailer.services')
    );
  }

  /**
   * @return array
   */
  public function build() {
    $build = [];
    if ($this->mailermailer->getMailerApi() != NULL) {
      $build = \Drupal::formBuilder()->getForm('Drupal\mailermailer\Form\MailerMailerForm');
    }
    return $build;
  }

}
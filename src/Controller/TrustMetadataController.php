<?php

namespace Drupal\ucb_trust_schema\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for trust metadata management.
 */
class TrustMetadataController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a TrustMetadataController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Returns a modal form for editing trust metadata.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to open the modal form.
   */
  public function editForm(NodeInterface $node, Request $request) {
    $query = \Drupal::entityQuery('trust_metadata')
      ->condition('node_id', $node->id())
      ->accessCheck(FALSE);
    $ids = $query->execute();

    if (!empty($ids)) {
      $trust_metadata = \Drupal::entityTypeManager()->getStorage('trust_metadata')->load(reset($ids));
    }
    else {
      $trust_metadata = \Drupal::entityTypeManager()->getStorage('trust_metadata')->create(['node_id' => $node->id()]);
      $trust_metadata->save();
    }

    $form = $this->formBuilder->getForm('Drupal\ucb_trust_schema\Form\TrustMetadataForm', $trust_metadata);
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand('Edit Trust Metadata', $form));
    return $response;
  }

} 
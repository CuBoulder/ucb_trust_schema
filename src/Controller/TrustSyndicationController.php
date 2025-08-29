<?php

namespace Drupal\ucb_trust_schema\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;

/**
 * Controller for trust syndication operations.
 */
class TrustSyndicationController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a TrustSyndicationController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(AccountInterface $current_user, LoggerChannelFactoryInterface $logger_factory, Connection $database) {
    $this->currentUser = $current_user;
    $this->loggerFactory = $logger_factory;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('logger.factory'),
      $container->get('database')
    );
  }

  /**
   * Returns trust metadata for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing trust metadata.
   */
  public function view(NodeInterface $node) {
    $metadata = ucb_trust_schema_get_trust_metadata($node->id());
    
    if (!$metadata) {
      $metadata = [
        'trust_role' => '',
        'trust_scope' => '',
        'trust_contact' => '',
        'trust_topics' => [],
        'trust_syndication_enabled' => FALSE,
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $metadata
    ]);
  }

  /**
   * Saves trust metadata for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response indicating success or failure.
   */
  public function save(NodeInterface $node) {
    $request = \Drupal::request();
    $content = $request->getContent();
    $data = json_decode($content, TRUE);
    
    if (!$data) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Invalid request data.')
      ], 400);
    }

    // Ensure all required fields are present
    $metadata = [
      'trust_role' => $data['trust_role'] ?? '',
      'trust_scope' => $data['trust_scope'] ?? '',
      'trust_contact' => $data['trust_contact'] ?? '',
      'trust_topics' => $data['trust_topics'] ?? [],
      'trust_syndication_enabled' => $data['trust_syndication_enabled'] ?? FALSE,
    ];
    
    if (ucb_trust_schema_update_trust_metadata($node->id(), $metadata)) {
      // Get the updated metadata
      $updated_metadata = ucb_trust_schema_get_trust_metadata($node->id());
      
      return new JsonResponse([
        'success' => TRUE,
        'message' => $this->t('Trust metadata has been updated.'),
        'data' => $updated_metadata
      ]);
    }
    
    return new JsonResponse([
      'success' => FALSE,
      'message' => $this->t('Failed to update trust metadata.')
    ], 500);
  }

  /**
   * Access callback for the save action.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function saveAccess(NodeInterface $node) {
    return AccessResult::allowedIfHasPermission($this->currentUser, 'manage trust metadata');
  }

  /**
   * Checks access for the trust syndication page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(NodeInterface $node) {
    return AccessResult::allowedIf($this->currentUser->hasPermission('manage trust metadata'));
  }

  /**
   * Builds the trust syndication page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to syndicate.
   *
   * @return array
   *   A render array for the page.
   */
  public function content(NodeInterface $node) {
    // Get trust metadata
    $metadata = ucb_trust_schema_get_trust_metadata($node->id());

    // Build the page
    $build = [
      '#theme' => 'trust_syndication_page',
      '#node' => $node,
      '#metadata' => $metadata,
      '#attached' => [
        'library' => [
          'ucb_trust_schema/trust-syndication',
        ],
        'drupalSettings' => [
          'trustSchema' => $metadata,
        ],
      ],
    ];

    // Add page title
    $build['#title'] = $this->t('Trust Syndication: @title', ['@title' => $node->getTitle()]);

    return $build;
  }

  /**
   * Toggle trust syndication status for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to toggle syndication for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   JSON response for AJAX requests, redirect response otherwise.
   */
  public function toggleSyndication(NodeInterface $node, Request $request) {
    $metadata = ucb_trust_schema_get_trust_metadata($node->id());
    if (!$metadata) {
      $message = $this->t('This content type does not support trust syndication.');
      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $message
        ], 400);
      }
      $this->messenger()->addError($message);
      return new RedirectResponse($node->toUrl()->toString());
    }

    // Toggle the syndication status
    $enabled = !($metadata['trust_syndication_enabled'] ?? FALSE);
    
    if (ucb_trust_schema_toggle_syndication($node->id(), $enabled)) {
      $message = $enabled 
        ? $this->t('Trust syndication enabled for this content.')
        : $this->t('Trust syndication disabled for this content.');

      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => TRUE,
          'message' => $message,
          'newState' => $enabled
        ]);
      }

      $this->messenger()->addStatus($message);
      return new RedirectResponse($node->toUrl()->toString());
    }

    $message = $this->t('Failed to update trust syndication status.');
    if ($request->isXmlHttpRequest()) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $message
      ], 500);
    }
    $this->messenger()->addError($message);
    return new RedirectResponse($node->toUrl()->toString());
  }

  /**
   * Displays a list of nodes with trust syndication status.
   *
   * @return array
   *   A render array for the overview page.
   */
  public function overview() {
    $query = $this->database->select('node', 'n')
      ->fields('n', ['nid', 'title', 'type'])
      ->condition('n.type', ['article', 'page'], 'IN')
      ->orderBy('n.title', 'ASC');

    $nodes = $query->execute()->fetchAll();

    $rows = [];
    foreach ($nodes as $node) {
      $trust_metadata = ucb_trust_schema_get_trust_metadata($node->nid);
      
      $rows[] = [
        'data' => [
          'title' => [
            'data' => [
              '#type' => 'link',
              '#title' => $node->title,
              '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->nid]),
            ],
          ],
          'type' => $this->nodeTypeStorage->load($node->type)->label(),
          'trust_role' => $trust_metadata['trust_role'] ?? '',
          'trust_scope' => $trust_metadata['trust_scope'] ?? '',
          'timeliness' => $trust_metadata['timeliness'] ?? '',
          'audience' => $trust_metadata['audience'] ?? '',
          'trust_contact' => $trust_metadata['trust_contact'] ?? '',
          'syndication_status' => [
            'data' => [
              '#type' => 'markup',
              '#markup' => $trust_metadata['trust_syndication_enabled'] ? 'Enabled' : 'Disabled',
              '#attributes' => [
                'data-drupal-selector' => 'syndication-status',
              ],
            ],
          ],
          'operations' => [
            'data' => [
              '#type' => 'operations',
              '#links' => [
                'edit' => [
                  'title' => $this->t('Edit Trust Metadata'),
                  'url' => Url::fromRoute('ucb_trust_schema.edit', ['node' => $node->nid]),
                  'attributes' => [
                    'class' => ['trust-syndication-button'],
                    'data-node-id' => $node->nid,
                  ],
                ],
              ],
            ],
          ],
        ],
        'data-node-id' => $node->nid,
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Content type'),
        $this->t('Trust Role'),
        $this->t('Trust Scope'),
        $this->t('Timeliness'),
        $this->t('Audience'),
        $this->t('Maintainer Contact'),
        $this->t('Syndication Status'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['trust-syndication-overview'],
      ],
      '#empty' => $this->t('No content found.'),
    ];

    return $build;
  }
} 
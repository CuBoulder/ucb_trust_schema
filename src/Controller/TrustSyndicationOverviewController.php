<?php

namespace Drupal\ucb_trust_schema\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller for trust syndication overview.
 */
class TrustSyndicationOverviewController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Constructs a TrustSyndicationOverviewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PagerManagerInterface $pager_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('pager.manager')
    );
  }

  /**
   * Checks access for the overview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('manage trust metadata'));
  }

  /**
   * Builds the overview page.
   *
   * @return array
   *   A render array for the page.
   */
  public function content() {
    // Get filter parameters from request
    $request = \Drupal::request();
    $filters = [
      'trust_role' => $request->query->get('trust_role'),
      'trust_scope' => $request->query->get('trust_scope'),
      'trust_contact' => $request->query->get('trust_contact'),
      'syndication_status' => $request->query->get('syndication_status'),
      'sort' => $request->query->get('sort', 'title'),
      'order' => $request->query->get('order', 'asc'),
    ];

    // Build the filter form
    $build['filters'] = \Drupal::formBuilder()->getForm('Drupal\ucb_trust_schema\Form\TrustSyndicationFilterForm', $filters);

    // Get all nodes
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');
    
    $nids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    // Filter and sort nodes
    $filtered_nodes = [];
    foreach ($nodes as $node) {
      $metadata = ucb_trust_schema_get_trust_metadata($node->id());
      
      // Apply filters
      if (!empty($filters['trust_role']) && $metadata['trust_role'] !== $filters['trust_role']) {
        continue;
      }
      if (!empty($filters['trust_scope']) && $metadata['trust_scope'] !== $filters['trust_scope']) {
        continue;
      }
      if (!empty($filters['timeliness']) && $metadata['timeliness'] !== $filters['timeliness']) {
        continue;
      }
      if (!empty($filters['audience']) && $metadata['audience'] !== $filters['audience']) {
        continue;
      }
      if (!empty($filters['trust_contact']) && stripos($metadata['trust_contact'], $filters['trust_contact']) === FALSE) {
        continue;
      }
      if ($filters['syndication_status'] !== NULL && $metadata['trust_syndication_enabled'] != $filters['syndication_status']) {
        continue;
      }

      $filtered_nodes[] = [
        'node' => $node,
        'metadata' => $metadata,
      ];
    }

    // Sort nodes
    usort($filtered_nodes, function($a, $b) use ($filters) {
      $sort_field = $filters['sort'];
      $sort_order = $filters['order'] === 'desc' ? -1 : 1;

      $value_a = '';
      $value_b = '';

      switch ($sort_field) {
        case 'title':
          $value_a = $a['node']->getTitle();
          $value_b = $b['node']->getTitle();
          break;
        case 'type':
          $value_a = $a['node']->type->entity->label();
          $value_b = $b['node']->type->entity->label();
          break;
        case 'trust_role':
          $value_a = $a['metadata']['trust_role'] ?? '';
          $value_b = $b['metadata']['trust_role'] ?? '';
          break;
        case 'trust_scope':
          $value_a = $a['metadata']['trust_scope'] ?? '';
          $value_b = $b['metadata']['trust_scope'] ?? '';
          break;
        case 'timeliness':
          $value_a = $a['metadata']['timeliness'] ?? '';
          $value_b = $b['metadata']['timeliness'] ?? '';
          break;
        case 'audience':
          $value_a = $a['metadata']['audience'] ?? '';
          $value_b = $b['metadata']['audience'] ?? '';
          break;
        case 'trust_contact':
          $value_a = $a['metadata']['trust_contact'] ?? '';
          $value_b = $b['metadata']['trust_contact'] ?? '';
          break;
        case 'trust_syndication_enabled':
          $value_a = $a['metadata']['trust_syndication_enabled'] ?? FALSE;
          $value_b = $b['metadata']['trust_syndication_enabled'] ?? FALSE;
          break;
        default:
          $value_a = $a['node']->getTitle();
          $value_b = $b['node']->getTitle();
      }

      return strcasecmp($value_a, $value_b) * $sort_order;
    });

    // Apply paging
    $page = $this->pagerManager->createPager(count($filtered_nodes), 50)->getCurrentPage();
    $offset = $page * 50;
    $filtered_nodes = array_slice($filtered_nodes, $offset, 50);

    $rows = [];
    foreach ($filtered_nodes as $item) {
      $node = $item['node'];
      $metadata = $item['metadata'];
      
      $row = [];
      
      // Title column
      $row['title'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $node->getTitle(),
          '#url' => $node->toUrl(),
        ],
      ];
      
      // Content type column
      $row['type'] = [
        'data' => $node->type->entity->label(),
      ];
      
      // Trust metadata columns
      $row['trust_role'] = [
        'data' => $metadata['trust_role'] ?? '',
      ];
      $row['trust_scope'] = [
        'data' => $metadata['trust_scope'] ?? '',
      ];
      $row['timeliness'] = [
        'data' => $metadata['timeliness'] ?? '',
      ];
      $row['audience'] = [
        'data' => $metadata['audience'] ?? '',
      ];
      $row['trust_contact'] = [
        'data' => $metadata['trust_contact'] ?? '',
      ];
      
      // Syndication status column
      $row['syndication_status'] = [
        'data' => [
          '#type' => 'markup',
          '#markup' => isset($metadata['trust_syndication_enabled']) 
            ? ($metadata['trust_syndication_enabled'] ? $this->t('Enabled') : $this->t('Disabled'))
            : $this->t('Not available'),
        ],
        'data-drupal-selector' => 'syndication-status',
      ];
      
      // Operations column
      $operations = [];
      
      // Edit Trust Metadata link
      $operations['edit'] = [
        'title' => $this->t('Edit Trust Metadata'),
        'url' => Url::fromRoute('ucb_trust_schema.trust_syndication_page', ['node' => $node->id()]),
        'attributes' => [
          'class' => ['trust-syndication-button'],
          'data-node-id' => $node->id(),
        ],
      ];
      
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];
      
      $rows[] = $row;
    }

    // Build the table header with sortable columns
    $header = [
      'title' => [
        'data' => $this->t('Title'),
        'field' => 'title',
        'sort' => 'asc',
      ],
      'type' => [
        'data' => $this->t('Content type'),
        'field' => 'type',
      ],
      'trust_role' => [
        'data' => $this->t('Trust Role'),
        'field' => 'trust_role',
      ],
      'trust_scope' => [
        'data' => $this->t('Trust Scope'),
        'field' => 'trust_scope',
      ],
      'timeliness' => [
        'data' => $this->t('Timeliness'),
        'field' => 'timeliness',
      ],
      'audience' => [
        'data' => $this->t('Audience'),
        'field' => 'audience',
      ],
      'trust_contact' => [
        'data' => $this->t('Maintainer Contact'),
        'field' => 'trust_contact',
      ],
      'syndication_status' => [
        'data' => $this->t('Syndication Status'),
        'field' => 'trust_syndication_enabled',
      ],
      'operations' => $this->t('Operations'),
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No content found.'),
      '#attributes' => ['class' => ['trust-syndication-overview']],
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    $build['#attached']['library'][] = 'ucb_trust_schema/trust-syndication';

    // Wrap everything in a container like admin/content
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['trust-syndication-overview-page']],
      'content' => $build,
    ];

    return $build;
  }

} 
<?php

namespace Drupal\ucb_trust_schema\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;

/**
 * Controller for syndicated nodes.
 */
class SyndicatedNodeController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new SyndicatedNodeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ResourceTypeRepositoryInterface $resource_type_repository, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jsonapi.resource_type.repository'),
      $container->get('logger.factory')
    );
  }

  /**
   * Returns a list of syndicated nodes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function list(Request $request) {
    $logger = $this->loggerFactory->get('ucb_trust_schema');
    
    try {
      // Get all nodes with trust syndication enabled from our custom table
      $query = \Drupal::database()->select('trust_metadata', 'tm')
        ->fields('tm', ['nid'])
        ->condition('trust_syndication_enabled', 1);
      
      $nids = $query->execute()->fetchCol();
      $logger->debug('Found @count nodes with trust syndication enabled', ['@count' => count($nids)]);
      
      if (empty($nids)) {
        return new JsonResponse([
          'data' => [],
          'meta' => [
            'message' => 'No syndicated nodes found',
          ],
        ]);
      }
      
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      
      $data = [];
      foreach ($nodes as $node) {
        // Get trust metadata from our custom table
        $metadata = ucb_trust_schema_get_trust_metadata($node->id());
        if (!$metadata || !$metadata['trust_syndication_enabled']) {
          continue;
        }

        // Get the node URL
        $url = $node->toUrl()->setAbsolute()->toString();
        
        // Get the summary
        $summary = '';
        if ($node->hasField('body')) {
          $body = $node->get('body')->first();
          if ($body && !$body->isEmpty()) {
            $summary = $body->summary ?: $body->value;
          }
        }
        
        $data[] = [
          'id' => $node->id(),
          'type' => $node->bundle(),
          'uuid' => $node->uuid(),
          'attributes' => [
            'title' => $node->getTitle(),
            'url' => $url,
            'summary' => $summary,
            'trust_role' => $metadata['trust_role'],
            'trust_scope' => $metadata['trust_scope'],
            'trust_contact' => $metadata['trust_contact'],
            'trust_topics' => $metadata['trust_topics'],
            'trust_syndication_enabled' => $metadata['trust_syndication_enabled'],
          ],
        ];
      }
      
      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'count' => count($data),
        ],
      ]);
    }
    catch (\Exception $e) {
      $logger->error('Error fetching syndicated nodes: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [
          [
            'status' => '500',
            'title' => 'Internal Server Error',
            'detail' => 'An error occurred while fetching syndicated nodes.',
          ],
        ],
      ], 500);
    }
  }
} 
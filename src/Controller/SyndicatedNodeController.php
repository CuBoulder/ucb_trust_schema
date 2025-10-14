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
use Drupal\user\Entity\User;

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
   * Get default trust contacts (users with developer role).
   *
   * @return array
   *   Array of user data for developers.
   */
  protected function getDefaultTrustContacts() {
    $query = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('roles', 'developer')
      ->condition('status', 1)
      ->accessCheck(FALSE);
    
    $uids = $query->execute();
    $users = User::loadMultiple($uids);
    
    $contacts = [];
    foreach ($users as $user) {
      $contacts[] = [
        'id' => $user->id(),
        'name' => $user->getDisplayName(),
        'email' => $user->getEmail(),
      ];
    }
    
    return $contacts;
  }

  /**
   * Parse email addresses from a string.
   *
   * @param string $emails
   *   Comma-separated list of email addresses.
   *
   * @return array
   *   Array of contact data.
   */
  protected function parseEmailContacts($emails) {
    if (empty($emails)) {
      return [];
    }

    $email_list = array_map('trim', explode(',', $emails));
    $contacts = [];
    
    foreach ($email_list as $email) {
      $contacts[] = [
        'email' => $email,
      ];
    }
    
    return $contacts;
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
      // Get all trust metadata entities with syndication enabled
      $query = $this->entityTypeManager->getStorage('trust_metadata')->getQuery()
        ->condition('trust_syndication_enabled', TRUE)
        ->accessCheck(FALSE);
      
      $ids = $query->execute();
      $logger->debug('Found @count nodes with trust syndication enabled', ['@count' => count($ids)]);
      
      if (empty($ids)) {
        return new JsonResponse([
          'data' => [],
          'meta' => [
            'message' => 'No syndicated nodes found',
          ],
        ]);
      }
      
      $trust_metadata_entities = $this->entityTypeManager->getStorage('trust_metadata')->loadMultiple($ids);
      
      $data = [];
      foreach ($trust_metadata_entities as $trust_metadata) {
        $node = $trust_metadata->get('node_id')->entity;
        if (!$node) {
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
        
        // Check for article summary field
        if (empty($summary) && $node->hasField('field_ucb_article_summary')) {
          $article_summary = $node->get('field_ucb_article_summary')->first();
          if ($article_summary && !$article_summary->isEmpty()) {
            $summary = $article_summary->value;
          }
        }
        
        // Check for generic summary field
        if (empty($summary) && $node->hasField('field_summary')) {
          $generic_summary = $node->get('field_summary')->first();
          if ($generic_summary && !$generic_summary->isEmpty()) {
            $summary = $generic_summary->value;
          }
        }
        
        // For person pages, try to get a description or bio
        if (empty($summary) && $node->bundle() === 'person') {
          if ($node->hasField('field_ucb_person_bio')) {
            $bio = $node->get('field_ucb_person_bio')->first();
            if ($bio && !$bio->isEmpty()) {
              $summary = $bio->summary ?: $bio->value;
            }
          }
          elseif ($node->hasField('field_ucb_person_description')) {
            $description = $node->get('field_ucb_person_description')->first();
            if ($description && !$description->isEmpty()) {
              $summary = $description->value;
            }
          }
        }

        // Get the abstract
        $abstract = '';
        if ($node->hasField('field_abstract')) {
          $abstract_field = $node->get('field_abstract')->first();
          if ($abstract_field && !$abstract_field->isEmpty()) {
            $abstract = $abstract_field->value;
          }
        }

        // Get trust topics
        $trust_topics = [];
        foreach ($trust_metadata->get('trust_topics') as $topic) {
          if ($topic->entity) {
            $trust_topics[] = $topic->entity->getName();
          }
        }

        // Get trust contacts
        $trust_contacts = [];
        $trust_contact_value = $trust_metadata->get('trust_contact')->value;
        if (!empty($trust_contact_value)) {
          // If specific contacts are set, use those email addresses directly
          $trust_contacts = $this->parseEmailContacts($trust_contact_value);
        }
        else {
          // Otherwise use default developer contacts
          $trust_contacts = $this->getDefaultTrustContacts();
        }

        // Get content authority from site name
        $content_authority = \ucb_trust_schema_get_site_name();
        
        $data[] = [
          'id' => $node->id(),
          'type' => $node->bundle(),
          'uuid' => $node->uuid(),
          'attributes' => [
            'title' => $node->getTitle(),
            'url' => $url,
            'summary' => $summary,
            'abstract' => $abstract,
            'trust_role' => $trust_metadata->get('trust_role')->value,
            'trust_scope' => $trust_metadata->get('trust_scope')->value,
            'type' => $trust_metadata->get('type')->value,
            'timeliness' => $trust_metadata->get('timeliness')->value,
            'audience' => $trust_metadata->get('audience')->value,
            'trust_contact' => $trust_contacts,
            'trust_topics' => $trust_topics,
            'trust_syndication_enabled' => $trust_metadata->get('trust_syndication_enabled')->value,
            'content_authority' => $content_authority,
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
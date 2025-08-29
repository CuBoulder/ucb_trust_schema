<?php

namespace Drupal\ucb_trust_schema\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\ucb_trust_schema\Field\TrustTopicsFieldAccessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a custom resource type for trust schema.
 */
class TrustSchemaResourceTypeProvider {

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
   * The trust topics field accessor.
   *
   * @var \Drupal\ucb_trust_schema\Field\TrustTopicsFieldAccessor
   */
  protected $trustTopicsFieldAccessor;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new TrustSchemaResourceTypeProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\ucb_trust_schema\Field\TrustTopicsFieldAccessor $trust_topics_field_accessor
   *   The trust topics field accessor.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ResourceTypeRepositoryInterface $resource_type_repository,
    TrustTopicsFieldAccessor $trust_topics_field_accessor,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->trustTopicsFieldAccessor = $trust_topics_field_accessor;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Alters the resource type for nodes.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type to alter.
   */
  public function alterResourceType(ResourceType $resource_type) {
    $logger = $this->loggerFactory->get('ucb_trust_schema');
    $logger->debug('alterResourceType called for type: @type', ['@type' => $resource_type->getEntityTypeId()]);
    
    if ($resource_type->getEntityTypeId() === 'trust_metadata') {
      $logger->debug('Adding trust metadata fields to resource type');
      
      // Add all trust metadata fields
      $fields = [
        'trust_role',
        'trust_scope',
        'trust_contact',
        'timeliness',
        'audience',
        'trust_topics',
        'trust_syndication_enabled',
        'node_id',
        'syndication_consumer_sites',
        'syndication_total_views',
        'syndication_consumer_sites_list',
      ];
      
      foreach ($fields as $field) {
        // Determine field type based on field name
        $fieldType = 'string';
        $isComputed = FALSE;
        
        if ($field === 'trust_topics') {
          $fieldType = 'string';
          $isComputed = TRUE;
        }
        elseif (in_array($field, ['syndication_consumer_sites', 'syndication_total_views'])) {
          $fieldType = 'integer';
        }
        elseif ($field === 'syndication_consumer_sites_list') {
          $fieldType = 'string';
        }
        
        $resource_type->addField($field, [
          'fieldName' => $field,
          'publicName' => $field,
          'enabled' => TRUE,
          'fieldType' => $fieldType,
          'isComputed' => $isComputed,
        ]);
      }
      
      $logger->debug('Trust metadata fields added to resource type');
    }
    elseif ($resource_type->getEntityTypeId() === 'node') {
      $logger->debug('Adding trust metadata relationship to node resource type');
      
      // Add trust metadata relationship
      $resource_type->addRelationship('trust_metadata', [
        'fieldName' => 'trust_metadata',
        'publicName' => 'trust_metadata',
        'enabled' => TRUE,
        'fieldType' => 'entity_reference',
        'targetResourceType' => 'trust_metadata--trust_metadata',
      ]);
      
      $logger->debug('Trust metadata relationship added to node resource type');
    }
  }

} 
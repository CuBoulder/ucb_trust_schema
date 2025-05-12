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
    
    if ($resource_type->getEntityTypeId() === 'node') {
      $logger->debug('Adding trust_topics field to node resource type');
      
      $resource_type->addField('trust_topics', [
        'fieldName' => 'trust_topics',
        'publicName' => 'trust_topics',
        'enabled' => TRUE,
        'fieldType' => 'string',
        'isComputed' => TRUE,
        'fieldAccessor' => [$this->trustTopicsFieldAccessor, 'getTrustTopics'],
      ]);
      
      $logger->debug('trust_topics field added to node resource type');
    }
  }

} 
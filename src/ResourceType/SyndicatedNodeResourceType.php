<?php

namespace Drupal\ucb_trust_schema\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Resource type for syndicated nodes.
 */
class SyndicatedNodeResourceType extends ResourceType {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, $bundle, $resource_type_name) {
    parent::__construct($entity_type, $bundle, $resource_type_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFieldNames() {
    $fields = parent::getPublicFieldNames();
    
    // Add our custom trust metadata fields
    $trust_fields = [
      'trust_role',
      'trust_scope',
      'trust_contact',
      'trust_topics',
      'trust_syndication_enabled',
      'node_summary',
    ];
    
    return array_merge($fields, $trust_fields);
  }
} 
<?php

namespace Drupal\ucb_trust_schema\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldAccessorInterface;

/**
 * Custom field accessor for trust topics.
 */
class TrustTopicsFieldAccessor implements FieldAccessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TrustTopicsFieldAccessor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResourceObject $resource_object, string $field_name) {
    $node = $resource_object->getResource();
    return $this->getTrustTopics($node);
  }

  /**
   * Gets the trust topics for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get trust topics for.
   *
   * @return array
   *   An array of trust topic names.
   */
  protected function getTrustTopics($node) {
    return ucb_trust_schema_get_trust_metadata($node->id())['trust_topics'];
  }

} 
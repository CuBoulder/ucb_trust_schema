<?php

namespace Drupal\ucb_trust_schema\Service;

use Drupal\Core\Database\Query\Select;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\node\NodeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for handling Trust Schema JSON:API integration.
 */
class TrustSchemaJsonApiService {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new TrustSchemaJsonApiService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Alters field values in JSON:API response.
   *
   * @param mixed &$field_value
   *   The field value to alter.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity.
   */
  public function alterFieldValue(&$field_value, $field_name, NodeInterface $entity) {
    // Get trust metadata from our custom table
    $metadata = ucb_trust_schema_get_trust_metadata($entity->id());
    
    if (!$metadata || !$metadata['trust_syndication_enabled']) {
      $field_value = $field_name === 'trust_topics' ? [] : NULL;
      return;
    }

    // Map field names to metadata keys
    $field_value = $metadata[$field_name] ?? NULL;
  }

  /**
   * Alters field values for trust_metadata entities in JSON:API response.
   *
   * @param mixed &$field_value
   *   The field value to alter.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\ucb_trust_schema\Entity\TrustMetadata $entity
   *   The trust metadata entity.
   */
  public function alterTrustMetadataFieldValue(&$field_value, $field_name, $entity) {
    // Handle content_authority field specially
    if ($field_name === 'content_authority') {
      $field_value = $this->getContentAuthority();
      \Drupal::logger('ucb_trust_schema')->debug('Content authority set via service to: @value', ['@value' => $field_value]);
      return;
    }

    // For trust_metadata entities, we can directly access the field values
    // since they're stored as entity fields
    if ($entity->hasField($field_name)) {
      $field_value = $entity->get($field_name)->value;
    }
  }

  /**
   * Alters the node query for JSON:API.
   *
   * @param \Drupal\Core\Database\Query\Select $query
   *   The query to alter.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type.
   */
  public function alterNodeQuery(Select $query, ResourceType $resource_type) {
    // Create a subquery to get nodes with trust syndication enabled
    $subquery = \Drupal::database()->select('trust_metadata', 'tm')
      ->fields('tm', ['nid'])
      ->condition('trust_syndication_enabled', 1);
    
    // Add the condition to the main query
    $query->condition('node.nid', $subquery, 'IN');
  }

  /**
   * Gets the content authority from site name.
   *
   * @return string
   *   The site name.
   */
  public function getContentAuthority() {
    return \ucb_trust_schema_get_site_name();
  }
} 
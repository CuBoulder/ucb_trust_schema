<?php

namespace Drupal\ucb_trust_schema;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Trust Metadata entities.
 */
class TrustMetadataListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['node'] = $this->t('Node');
    $header['trust_role'] = $this->t('Trust Role');
    $header['trust_scope'] = $this->t('Trust Scope');
    $header['trust_syndication_enabled'] = $this->t('Syndication Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ucb_trust_schema\Entity\TrustMetadata $entity */
    $row['id'] = $entity->id();
    $row['node'] = $entity->get('node_id')->entity ? $entity->get('node_id')->entity->toLink() : '';
    $row['trust_role'] = $entity->get('trust_role')->value;
    $row['trust_scope'] = $entity->get('trust_scope')->value;
    $row['trust_syndication_enabled'] = $entity->get('trust_syndication_enabled')->value ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

} 
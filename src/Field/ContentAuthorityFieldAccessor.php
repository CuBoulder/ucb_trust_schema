<?php

namespace Drupal\ucb_trust_schema\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field item list for content_authority field.
 */
class ContentAuthorityFieldAccessor extends FieldItemList implements FieldItemListInterface {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    // Always compute fresh from site configuration - don't cache
    $content_authority = \Drupal\ucb_trust_schema\Entity\TrustMetadata::getContentAuthority();
    $this->list[0] = $this->createItem(0, $content_authority);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    // Force fresh computation every time by clearing the cache first
    $this->list = [];
    $this->ensureComputedValue();
    return parent::getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Force fresh computation every time by clearing the cache first
    $this->list = [];
    $this->ensureComputedValue();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function get($index = 0) {
    // Force fresh computation every time by clearing the cache first
    $this->list = [];
    $this->ensureComputedValue();
    return parent::get($index);
  }

}

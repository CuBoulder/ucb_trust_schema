<?php

namespace Drupal\ucb_trust_schema;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\ucb_trust_schema\Form\TrustMetadataFilterForm;
use Drupal\Core\Form\FormBuilderInterface;

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
    $header['trust_topics'] = $this->t('Trust Topics');
    $header['trust_syndication_enabled'] = $this->t('Syndication Enabled');
    $header['consumer_sites'] = $this->t('Consumer Sites');
    $header['total_views'] = $this->t('Total Views');
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
    $topics = [];
    foreach ($entity->get('trust_topics') as $topic) {
      if ($topic->entity) {
        $topics[] = $topic->entity->label();
      }
    }
    $row['trust_topics'] = implode(', ', $topics);
    $row['trust_syndication_enabled'] = $entity->get('trust_syndication_enabled')->value ? $this->t('Yes') : $this->t('No');
    
    // Analytics columns
    $row['consumer_sites'] = $entity->get('syndication_consumer_sites')->value ?? 0;
    $row['total_views'] = $entity->get('syndication_total_views')->value ?? 0;
    
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $form = \Drupal::formBuilder()->getForm(TrustMetadataFilterForm::class);
    $build = [];
    $build['filter_form'] = $form;
    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->accessCheck(FALSE);
    $request = \Drupal::request();
    $params = $request->query->all();

    if (!empty($params['trust_role'])) {
      $query->condition('trust_role', $params['trust_role']);
    }
    if (!empty($params['trust_scope'])) {
      $query->condition('trust_scope', $params['trust_scope']);
    }
    if (!empty($params['trust_topics'])) {
      $query->condition('trust_topics', $params['trust_topics']);
    }
    if (array_key_exists('trust_syndication_enabled', $params) && $params['trust_syndication_enabled'] !== '' && isset($params['trust_syndication_enabled'])) {
      $query->condition('trust_syndication_enabled', (bool) $params['trust_syndication_enabled']);
    }

    // Add table sort and pager.
    $header = $this->buildHeader();
    $query->tableSort($header);
    $query->pager($this->limit);

    return $query->execute();
  }

} 
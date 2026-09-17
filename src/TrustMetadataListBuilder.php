<?php

namespace Drupal\ucb_trust_schema;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ucb_trust_schema\Form\TrustMetadataFilterForm;

/**
 * Defines a class to build a listing of Trust Metadata entities.
 */
class TrustMetadataListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = [
      'data' => $this->t('ID'),
      'field' => 'id',
      'specifier' => 'id',
    ];
    $header['node'] = [
      'data' => $this->t('Page title'),
      'field' => 'node_id',
      'specifier' => 'node_id',
    ];
    $header['trust_role'] = [
      'data' => $this->t('Trust Role'),
      'field' => 'trust_role',
      'specifier' => 'trust_role',
    ];
    $header['trust_scope'] = [
      'data' => $this->t('Trust Scope'),
      'field' => 'trust_scope',
      'specifier' => 'trust_scope',
    ];
    $header['timeliness'] = [
      'data' => $this->t('Timeliness'),
      'field' => 'timeliness',
      'specifier' => 'timeliness',
    ];
    $header['audience'] = [
      'data' => $this->t('Audience'),
      'field' => 'audience',
      'specifier' => 'audience',
    ];
    $header['trust_topics'] = [
      'data' => $this->t('Subjects'),
    ];
    $header['trust_syndication_enabled'] = [
      'data' => $this->t('Syndication Enabled'),
      'field' => 'trust_syndication_enabled',
      'specifier' => 'trust_syndication_enabled',
    ];
    $header['consumer_sites'] = [
      'data' => $this->t('Consumer Sites'),
      'field' => 'syndication_consumer_sites',
      'specifier' => 'syndication_consumer_sites',
    ];
    $header['total_views'] = [
      'data' => $this->t('Total Views'),
      'field' => 'syndication_total_views',
      'specifier' => 'syndication_total_views',
    ];
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
    $row['timeliness'] = $entity->get('timeliness')->value ?: '';
    $row['audience'] = ucb_trust_schema_format_audience(ucb_trust_schema_get_audience_values($entity));
    $topics = [];
    foreach ($entity->get('trust_topics') as $topic) {
      if ($topic->entity) {
        $topics[] = $topic->entity->label();
      }
    }
    $row['trust_topics'] = implode(', ', $topics);
    $row['trust_syndication_enabled'] = $entity->get('trust_syndication_enabled')->value ? $this->t('Yes') : $this->t('No');

    // Analytics columns.
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
    $request = \Drupal::request();
    $params = $request->query->all();

    // Sorting by page title requires a join to node_field_data.
    if ($request->query->get('sort') === 'node') {
      return $this->getEntityIdsSortedByNodeTitle($params);
    }

    $query = $this->getStorage()->getQuery();
    $query->accessCheck(FALSE);
    $this->applyEntityQueryFilters($query, $params);

    $header = $this->buildHeader();
    $query->tableSort($header);
    $query->pager($this->limit);

    return $query->execute();
  }

  /**
   * Applies filter conditions to a trust metadata entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query.
   * @param array $params
   *   The request query parameters.
   */
  protected function applyEntityQueryFilters($query, array $params) {
    if (!empty($params['title'])) {
      $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('title', $params['title'], 'CONTAINS')
        ->execute();
      if (empty($nids)) {
        $query->condition('id', 0);
        return;
      }
      $query->condition('node_id', $nids, 'IN');
    }
    if (!empty($params['trust_role'])) {
      $query->condition('trust_role', $params['trust_role']);
    }
    if (!empty($params['trust_scope'])) {
      $query->condition('trust_scope', $params['trust_scope']);
    }
    if (!empty($params['timeliness'])) {
      $query->condition('timeliness', $params['timeliness']);
    }
    if (!empty($params['audience'])) {
      $query->condition('audience', $params['audience']);
    }
    if (!empty($params['trust_topics'])) {
      $query->condition('trust_topics', $params['trust_topics']);
    }
    if (array_key_exists('trust_syndication_enabled', $params) && $params['trust_syndication_enabled'] !== '' && isset($params['trust_syndication_enabled'])) {
      $query->condition('trust_syndication_enabled', (bool) $params['trust_syndication_enabled']);
    }
  }

  /**
   * Loads entity IDs sorted by the related node title.
   *
   * @param array $params
   *   The request query parameters.
   *
   * @return array
   *   Entity IDs for the current page.
   */
  protected function getEntityIdsSortedByNodeTitle(array $params) {
    $request = \Drupal::request();
    $order = strtolower($request->query->get('order', 'asc')) === 'desc' ? 'DESC' : 'ASC';

    $query = \Drupal::database()->select('trust_metadata', 'tm');
    $query->addField('tm', 'id');
    $query->innerJoin('node_field_data', 'nfd', 'tm.node_id = nfd.nid AND nfd.default_langcode = 1');

    $this->applyDatabaseQueryFilters($query, $params);

    $query->orderBy('nfd.title', $order);
    $query->orderBy('tm.id', 'ASC');

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($this->limit);

    return $query->execute()->fetchCol();
  }

  /**
   * Applies filter conditions to a trust metadata database query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The database select query, with alias "tm" for trust_metadata.
   * @param array $params
   *   The request query parameters.
   */
  protected function applyDatabaseQueryFilters(SelectInterface $query, array $params) {
    if (!empty($params['title'])) {
      $query->condition('nfd.title', '%' . \Drupal::database()->escapeLike($params['title']) . '%', 'LIKE');
    }
    if (!empty($params['trust_role'])) {
      $query->condition('tm.trust_role', $params['trust_role']);
    }
    if (!empty($params['trust_scope'])) {
      $query->condition('tm.trust_scope', $params['trust_scope']);
    }
    if (!empty($params['timeliness'])) {
      $query->condition('tm.timeliness', $params['timeliness']);
    }
    if (!empty($params['audience'])) {
      $query->innerJoin('trust_metadata__audience', 'aud', 'tm.id = aud.entity_id AND aud.deleted = 0');
      $query->condition('aud.audience_value', $params['audience']);
    }
    if (!empty($params['trust_topics'])) {
      $query->innerJoin('trust_metadata__trust_topics', 'tt', 'tm.id = tt.entity_id AND tt.deleted = 0');
      $query->condition('tt.trust_topics_target_id', $params['trust_topics']);
    }
    if (array_key_exists('trust_syndication_enabled', $params) && $params['trust_syndication_enabled'] !== '' && isset($params['trust_syndication_enabled'])) {
      $query->condition('tm.trust_syndication_enabled', (int) $params['trust_syndication_enabled']);
    }
  }

}

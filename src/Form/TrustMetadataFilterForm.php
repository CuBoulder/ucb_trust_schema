<?php

namespace Drupal\ucb_trust_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter form for the Trust Metadata admin listing.
 */
class TrustMetadataFilterForm extends FormBase {

  /**
   * Filter query parameter keys.
   *
   * @var string[]
   */
  protected const FILTER_KEYS = [
    'title',
    'trust_role',
    'trust_scope',
    'timeliness',
    'audience',
    'trust_topics',
    'trust_syndication_enabled',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trust_metadata_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $query = $request->query->all();

    $form['#attributes']['class'][] = 'trust-metadata-filter-form';
    $form['#attached']['library'][] = 'ucb_trust_schema/filter_form';

    $form['title_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['filter-row', 'filter-row-title']],
      '#tree' => FALSE,
    ];

    $form['title_row']['title'] = [
      '#type' => 'search',
      '#title' => $this->t('Page title'),
      '#default_value' => $query['title'] ?? '',
      '#attributes' => ['class' => ['filter-field', 'filter-field-title']],
      '#parents' => ['title'],
      '#size' => 40,
      '#maxlength' => 255,
    ];

    // First row of filters.
    $form['row1'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['filter-row']],
      '#tree' => FALSE,
    ];

    $form['row1']['trust_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Role'),
      '#options' => [
        '' => $this->t('- Any -'),
        'primary_source' => $this->t('Primary Source'),
        'secondary_source' => $this->t('Secondary Source'),
        'subject_matter_contributor' => $this->t('Subject Matter Contributor/Expert'),
        'unverified' => $this->t('Unverified'),
      ],
      '#default_value' => $query['trust_role'] ?? '',
      '#attributes' => ['class' => ['filter-field']],
      '#parents' => ['trust_role'],
    ];

    $form['row1']['trust_scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Scope'),
      '#options' => [
        '' => $this->t('- Any -'),
        'department_level' => $this->t('Department-level'),
        'college_level' => $this->t('College-level'),
        'administrative_unit' => $this->t('Administrative-unit'),
        'campus_wide' => $this->t('Campus-wide'),
      ],
      '#default_value' => $query['trust_scope'] ?? '',
      '#attributes' => ['class' => ['filter-field']],
      '#parents' => ['trust_scope'],
    ];

    $form['row1']['timeliness'] = [
      '#type' => 'select',
      '#title' => $this->t('Timeliness'),
      '#options' => [
        '' => $this->t('- Any -'),
        'evergreen' => $this->t('Evergreen'),
        'fall_semester' => $this->t('Fall Semester'),
        'spring_semester' => $this->t('Spring Semester'),
        'summer_semester' => $this->t('Summer Semester'),
        'winter_semester' => $this->t('Winter Semester'),
      ],
      '#default_value' => $query['timeliness'] ?? '',
      '#attributes' => ['class' => ['filter-field']],
      '#parents' => ['timeliness'],
    ];

    // Second row of filters.
    $form['row2'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['filter-row']],
      '#tree' => FALSE,
    ];

    $form['row2']['audience'] = [
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#options' => [
        '' => $this->t('- Any -'),
        'students' => $this->t('Students'),
        'faculty' => $this->t('Faculty'),
        'staff' => $this->t('Staff'),
        'alumni' => $this->t('Alumni'),
      ],
      '#default_value' => $query['audience'] ?? '',
      '#attributes' => ['class' => ['filter-field']],
      '#parents' => ['audience'],
    ];

    // Get all trust topics.
    $topic_options = ['' => $this->t('- Any -')];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('trust_topics');
    foreach ($terms as $term) {
      $topic_options[$term->tid] = $term->name;
    }
    $form['row2']['trust_topics'] = [
      '#type' => 'select',
      '#title' => $this->t('Subjects'),
      '#options' => $topic_options,
      '#default_value' => $query['trust_topics'] ?? '',
      '#attributes' => ['class' => ['filter-field']],
      '#parents' => ['trust_topics'],
    ];

    $form['row2']['trust_syndication_enabled'] = [
      '#type' => 'select',
      '#title' => $this->t('Syndication Enabled'),
      '#options' => [
        '' => $this->t('- Any -'),
        '1' => $this->t('Yes'),
        '0' => $this->t('No'),
      ],
      '#default_value' => $query['trust_syndication_enabled'] ?? '',
      '#attributes' => ['class' => ['filter-field']],
      '#parents' => ['trust_syndication_enabled'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['filter-actions']],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    if ($this->hasActiveFilters($query)) {
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
      ];
    }

    // Use GET method for filtering.
    $form['#method'] = 'get';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $params = $this->buildFilterQuery($form_state);
    $form_state->setRedirect('<current>', [], ['query' => $params]);
  }

  /**
   * Resets the filter selections.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('<current>');
  }

  /**
   * Builds query parameters from active filter values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Query parameters for the listing route.
   */
  protected function buildFilterQuery(FormStateInterface $form_state): array {
    $params = [];
    foreach (self::FILTER_KEYS as $key) {
      $value = $form_state->getValue($key);
      if ($value !== '' && $value !== NULL) {
        $params[$key] = $value;
      }
    }
    return $params;
  }

  /**
   * Determines whether any filters are currently active.
   *
   * @param array $query
   *   The current request query parameters.
   *
   * @return bool
   *   TRUE if one or more filters are active.
   */
  protected function hasActiveFilters(array $query): bool {
    foreach (self::FILTER_KEYS as $key) {
      if (!empty($query[$key])) {
        return TRUE;
      }
    }
    return FALSE;
  }

}

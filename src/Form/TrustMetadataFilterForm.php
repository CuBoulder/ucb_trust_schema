<?php

namespace Drupal\ucb_trust_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

class TrustMetadataFilterForm extends FormBase {
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

    $form['trust_role'] = [
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
    ];

    $form['trust_scope'] = [
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
    ];

    $form['timeliness'] = [
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
    ];

    $form['audience'] = [
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
    ];

    // Get all trust topics.
    $topic_options = ['' => $this->t('- Any -')];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('trust_topics');
    foreach ($terms as $term) {
      $topic_options[$term->tid] = $term->name;
    }
    $form['trust_topics'] = [
      '#type' => 'select',
      '#title' => $this->t('Subjects'),
      '#options' => $topic_options,
      '#default_value' => $query['trust_topics'] ?? '',
    ];

    $form['trust_syndication_enabled'] = [
      '#type' => 'select',
      '#title' => $this->t('Syndication Enabled'),
      '#options' => [
        '' => $this->t('- Any -'),
        '1' => $this->t('Yes'),
        '0' => $this->t('No'),
      ],
      '#default_value' => $query['trust_syndication_enabled'] ?? '',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    // Use GET method for filtering.
    $form['#method'] = 'get';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to the same page with query parameters for filtering.
    $params = [];
    foreach ([
      'trust_role',
      'trust_scope',
      'timeliness',
      'audience',
      'trust_topics',
      'trust_syndication_enabled',
    ] as $key) {
      $value = $form_state->getValue($key);
      if ($value !== '' && $value !== NULL) {
        $params[$key] = $value;
      }
    }
    $form_state->setRedirect('<current>', [], ['query' => $params]);
  }
} 
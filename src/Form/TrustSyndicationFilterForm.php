<?php

namespace Drupal\ucb_trust_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for filtering trust syndication content.
 */
class TrustSyndicationFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trust_syndication_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $filters = []) {
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['trust-syndication-filters', 'clearfix'],
      ],
    ];

    $form['filters']['trust_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Role'),
      '#options' => [
        '' => $this->t('- Any -'),
        'primary_source' => $this->t('Primary Source'),
        'secondary_source' => $this->t('Secondary Source'),
        'subject_matter_contributor' => $this->t('Subject Matter Contributor'),
        'unverified' => $this->t('Unverified'),
      ],
      '#default_value' => $filters['trust_role'] ?? '',
      '#attributes' => ['class' => ['trust-syndication-filter']],
    ];

    $form['filters']['trust_scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Scope'),
      '#options' => [
        '' => $this->t('- Any -'),
        'department_level' => $this->t('Department Level'),
        'college_level' => $this->t('College Level'),
        'campus_wide' => $this->t('Campus Wide'),
      ],
      '#default_value' => $filters['trust_scope'] ?? '',
      '#attributes' => ['class' => ['trust-syndication-filter']],
    ];

    $form['filters']['trust_contact'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maintainer Contact'),
      '#default_value' => $filters['trust_contact'] ?? '',
      '#size' => 20,
      '#attributes' => ['class' => ['trust-syndication-filter']],
    ];

    $form['filters']['syndication_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Syndication Status'),
      '#options' => [
        '' => $this->t('- Any -'),
        '1' => $this->t('Enabled'),
        '0' => $this->t('Disabled'),
      ],
      '#default_value' => $filters['syndication_status'] ?? '',
      '#attributes' => ['class' => ['trust-syndication-filter']],
    ];

    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['trust-syndication-actions']],
    ];

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['trust-syndication-submit']],
    ];

    $form['filters']['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => Url::fromRoute('ucb_trust_schema.overview'),
      '#attributes' => [
        'class' => ['button', 'trust-syndication-reset'],
      ],
    ];

    // Add CSS for horizontal layout
    $form['#attached']['library'][] = 'ucb_trust_schema/trust-syndication';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filters = [];
    
    // Get filter values
    $trust_role = $form_state->getValue('trust_role');
    $trust_scope = $form_state->getValue('trust_scope');
    $trust_contact = $form_state->getValue('trust_contact');
    $syndication_status = $form_state->getValue('syndication_status');

    // Build query parameters
    if (!empty($trust_role)) {
      $filters['trust_role'] = $trust_role;
    }
    if (!empty($trust_scope)) {
      $filters['trust_scope'] = $trust_scope;
    }
    if (!empty($trust_contact)) {
      $filters['trust_contact'] = $trust_contact;
    }
    if ($syndication_status !== '') {
      $filters['syndication_status'] = $syndication_status;
    }

    // Redirect to the overview page with filters
    $form_state->setRedirect('ucb_trust_schema.overview', $filters);
  }

} 
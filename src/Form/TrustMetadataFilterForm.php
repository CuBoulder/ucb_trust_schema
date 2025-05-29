<?php

namespace Drupal\ucb_trust_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for filtering trust metadata.
 */
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
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['trust-metadata-filters'],
      ],
    ];

    $form['filters']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->getRequest()->query->get('title'),
      '#size' => 30,
    ];

    $form['filters']['trust_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Role'),
      '#options' => [
        '' => $this->t('- Any -'),
        'primary_source' => $this->t('Primary Source'),
        'secondary_source' => $this->t('Secondary Source'),
      ],
      '#default_value' => $this->getRequest()->query->get('trust_role'),
    ];

    $form['filters']['trust_scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Scope'),
      '#options' => [
        '' => $this->t('- Any -'),
        'college_level' => $this->t('College Level'),
        'department_level' => $this->t('Department Level'),
      ],
      '#default_value' => $this->getRequest()->query->get('trust_scope'),
    ];

    $form['filters']['trust_syndication_enabled'] = [
      '#type' => 'select',
      '#title' => $this->t('Syndication Status'),
      '#options' => [
        '' => $this->t('- Any -'),
        '1' => $this->t('Enabled'),
        '0' => $this->t('Disabled'),
      ],
      '#default_value' => $this->getRequest()->query->get('trust_syndication_enabled'),
    ];

    $form['filters']['actions'] = [
      '#type' => 'actions',
    ];

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $form['filters']['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => Url::fromRoute('entity.trust_metadata.collection'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];
    foreach ($form_state->getValues() as $key => $value) {
      if ($value !== '' && $key !== 'submit' && $key !== 'form_build_id' && $key !== 'form_token' && $key !== 'form_id' && $key !== 'op') {
        $query[$key] = $value;
      }
    }
    $form_state->setRedirect('entity.trust_metadata.collection', [], ['query' => $query]);
  }
} 
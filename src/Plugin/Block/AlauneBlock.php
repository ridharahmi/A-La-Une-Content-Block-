<?php

namespace Drupal\ala_une_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'AlauneBlock' block.
 *
 * @Block(
 *  id = "ala_une_block",
 *  admin_label = @Translation("A la une block Content"),
 * )
 */
class AlauneBlock extends BlockBase
{

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return parent::defaultConfiguration() + [
                'content_alaune' => [],
            ];
    }


    /**
     * Overrides \Drupal\Core\Block\BlockBase::blockForm().
     *
     * Adds body and description fields to the block configuration form.
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $form = parent::blockForm($form, $form_state);
        $config = $this->getConfiguration();

        $items = [];
        if ($config['content_alaune']) {
            $items = Node::loadMultiple($config['content_alaune']);
        }

        $form['#tree'] = TRUE;

        $form['items_fieldset'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('List items'),
            '#prefix' => '<div id="items-fieldset-wrapper">',
            '#suffix' => '</div>',
        ];

        if (!$form_state->has('num_items')) {
            $form_state->set('num_items', count($config['content_alaune']));
        }
        $name_field = $form_state->get('num_items');
        for ($i = 0; $i < $name_field; $i++) {
            $items = array_values($items);
            $form['items_fieldset']['items'][$i] = [
                '#type' => 'entity_autocomplete',
                '#target_type' => 'node',
                '#title' => t('Item'),
                '#description' => t('Use autocomplete to find it'),
                '#selection_handler' => 'default',
                '#selection_settings' => array(
                    'target_bundles' => array('article'),
                ),
                '#default_value' => $items[$i],
            ];
        }

        $form['items_fieldset']['actions'] = [
            '#type' => 'actions',
        ];

        $form['items_fieldset']['actions']['add_item'] = [
            '#type' => 'submit',
            '#value' => t('Add Item'),
            '#submit' => [[$this, 'addOne']],
            '#ajax' => [
                'callback' => [$this, 'addmoreCallback'],
                'wrapper' => 'items-fieldset-wrapper',
            ],
        ];

        if ($name_field > 1) {
            $form['items_fieldset']['actions']['remove_item'] = [
                '#type' => 'submit',
                '#value' => t('Remove Item'),
                '#submit' => [[$this, 'removeCallback']],
                '#ajax' => [
                    'callback' => [$this, 'addmoreCallback'],
                    'wrapper' => 'items-fieldset-wrapper',
                ]
            ];
        }

        return $form;
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function addOne(array &$form, FormStateInterface $form_state)
    {
        $name_field = $form_state->get('num_items');
        $add_button = $name_field + 1;
        $form_state->set('num_items', $add_button);
        $form_state->setRebuild();
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * @return mixed
     */
    public function addmoreCallback(array &$form, FormStateInterface $form_state)
    {

        return $form['settings']['items_fieldset'];
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function removeCallback(array &$form, FormStateInterface $form_state)
    {
        $name_field = $form_state->get('num_items');
        if ($name_field > 1) {
            $remove_button = $name_field - 1;
            $form_state->set('num_items', $remove_button);
        }
        $form_state->setRebuild();
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state)
    {
        foreach ($form_state->getValues() as $key => $value) {
            if ($key === 'items_fieldset') {
                if (isset($value['items'])) {
                    $items = $value['items'];
                    foreach ($items as $key => $item) {
                        if ($item === '' || !$item) {
                            unset($items[$key]);
                        }
                    }
                    $this->configuration['content_alaune'] = $items;
                }
            }
        }

    }


    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $nodes = [];
        if (isset($this->configuration['content_alaune'])) {
            if (count($this->configuration['content_alaune']) > 0) {
                $nids = $this->configuration['content_alaune'];
                $nodes = Node::loadMultiple($nids);

            }
        }

        return array(
            '#theme' => 'ALaUne',
            '#nodes' => $nodes
        );
    }


}
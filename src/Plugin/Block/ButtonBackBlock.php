<?php

namespace Drupal\civicrm_view_phenix\Plugin\Block;

use Drupal\node\Entity\Node;
use \Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
/**
 * Provides a 'Back button' block.
 *
 * @Block(
 *  id = "back_button_block",
 *  admin_label = @Translation("Back button block"),
 *  category = @Translation("Cutom button back blocks"),
 *  context_definitions = {
 *  }
 * )
 */
class ButtonBackBlock  extends BlockBase  {

  /**
   * {@inheritdoc}
   */
  public function build() {
    \Drupal::service('cache.render')->invalidateAll();
    
    $html = '<a href="' . \Drupal::request()->server->get('HTTP_REFERER') . '" class="button button-go-back js-form-submit form-submit">Retour</a>';

    \Drupal::service('page_cache_kill_switch')->trigger();

    return [
      '#markup' => $html,
      '#cache' => ['max-age' => 0],
    ];
  }

}

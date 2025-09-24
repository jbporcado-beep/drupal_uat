<?php
namespace Drupal\admin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Provides a Save Cooperative button block.
 *
 * @Block(
 *   id = "save_coop_button",
 *   admin_label = @Translation("Save Cooperative Button")
 * )
 */
class SaveCoopButtonBlock extends BlockBase {
  public function build() {
    $route_name = \Drupal::routeMatch()->getRouteName();
    if (in_array($route_name, ['cooperative.edit', 'cooperative.edit.branches'])) {
      $id = \Drupal::routeMatch()->getParameter('id');
      return [
        '#type' => 'link',
        '#title' => $this->t('Save Changes'),
        '#url' => Url::fromRoute('cooperative.edit', ['id' => $id]),
        '#attributes' => [
          'class' => ['btn', 'coop-save-btn'],
        ],
      ];
    }
    return [];
  }
}
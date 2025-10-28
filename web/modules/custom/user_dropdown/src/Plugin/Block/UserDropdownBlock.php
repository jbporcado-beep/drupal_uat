<?php
namespace Drupal\user_dropdown\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a User Dropdown block.
 *
 * @Block(
 *   id = "user_dropdown_block",
 *   admin_label = @Translation("User Dropdown")
 * )
 */
class UserDropdownBlock extends BlockBase {
  public function build() {
    $current_user = \Drupal::currentUser();
    if ($current_user->isAuthenticated()) {
      $username = $current_user->getDisplayName();
      $logout_url = Url::fromRoute('user.logout')->toString();

      return [
        '#type' => 'inline_template',
        '#template' => '
          <div class="user-dropdown">
            <button class="user-dropdown-toggle user-avatar-btn" aria-haspopup="true" aria-expanded="false">
              <i class="fa fa-user-circle user-avatar-icon"></i>
            </button>

            <div class="user-dropdown-menu">
              <span class="username">{{ username }}</span>
              <a href="{{ logout_url }}" class="logout-btn">Logout</a>
            </div>
          </div>
        ',
        '#context' => [
          'username' => $username,
          'logout_url' => $logout_url,
        ],
        '#attached' => [
          'library' => [
            'user_dropdown/user-dropdown',
          ],
        ],
        '#cache' => [
          'contexts' => ['user'],
        ],
      ];
    }

    return [];
  }
}
<?php

namespace Drupal\Tests\flag_fse\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UserSearch entity reference selection plugin.
 *
 * @group flag_fse
 */
class FlagFseUserSearchPluginTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * Flag module doesn't properly declare schema for dynamic linkTypeConfig.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'flag',
    'flag_fse',
    'user',
  ];

  /**
   * Tests that users can be searched by email.
   */
  public function testSearchByEmail() {
    $user1 = $this->drupalCreateUser([], 'testuser1');
    $user1->setEmail('test1@example.com');
    $user1->save();

    $user2 = $this->drupalCreateUser([], 'testuser2');
    $user2->setEmail('test2@example.com');
    $user2->save();

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');

    $options = [
      'target_type' => 'user',
      'handler' => 'flag_fse:user',
      'handler_settings' => [
        'include_anonymous' => FALSE,
      ],
    ];

    $handler = $selection_manager->getInstance($options);

    // Search by email.
    $results = $handler->getReferenceableEntities('test1@', 'CONTAINS', 10);

    // Should find user1.
    $this->assertNotEmpty($results, 'Search results should not be empty');

    $found_user1 = FALSE;
    foreach ($results as $bundle => $users) {
      // Keys in $users are string representations of user IDs
      if (array_key_exists((string) $user1->id(), $users)) {
        $found_user1 = TRUE;
        break;
      }
    }

    $this->assertTrue($found_user1, 'User 1 should be found by email search.');
  }

}

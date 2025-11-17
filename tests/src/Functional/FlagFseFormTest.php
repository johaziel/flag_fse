<?php

namespace Drupal\Tests\flag_fse\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\flag\Entity\Flag;

/**
 * Tests the Flag FSE user interface.
 *
 * @group flag_fse
 */
class FlagFseFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'flag',
    'flag_fse',
    'user',
    'system',
  ];

  /**
   * Test flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Regular user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regularUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create article content type.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    // Create a flag with FSE link type.
    $this->flag = Flag::create([
      'id' => 'bookmark',
      'label' => 'Bookmark',
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'flag_fse',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [
        'flag_fse_plugin_fallback' => 'reload',
        'flag_fse_link_title' => 'Flag for someone else',
        'flag_fse_confirmation' => 'Flag this content for a user?',
        'flag_fse_create_button' => 'Create flagging',
        'flag_fse_form_behavior' => 'default',
      ],
    ]);
    $this->flag->save();

    // Create test node.
    $this->node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
      'status' => 1,
    ]);
    $this->node->save();

    // Create admin user with FSE permission.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer users',
      'flag fse bookmark',
    ]);

    // Create regular user.
    $this->regularUser = $this->drupalCreateUser([
      'access content',
    ]);
  }

  /**
   * Tests that FSE link appears for authorized users.
   */
  public function testFseLinkVisibility() {
    // Login as admin (has permission).
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $this->node->id());

    // Should see FSE link.
    $this->assertSession()->pageTextContains('Flag for someone else');

    // Logout and login as regular user.
    $this->drupalLogout();
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('node/' . $this->node->id());

    // Should NOT see FSE link.
    $this->assertSession()->pageTextNotContains('Flag for someone else');
  }

  /**
   * Tests flagging for an existing user.
   */
  public function testFlagForExistingUser() {
    $this->drupalLogin($this->adminUser);

    // Create a target user to flag for.
    $target_user = $this->drupalCreateUser(['access content']);

    // Go to FSE form.
    $this->drupalGet('flag_fse/confirm/flag/bookmark/' . $this->node->id() . '/default');
    $this->assertSession()->statusCodeEquals(200);

    // Verify form elements exist.
    $this->assertSession()->fieldExists('uid');
    $this->assertSession()->buttonExists('Create flagging');

    // Submit the form with target user.
    $this->submitForm([
      'uid' => $target_user->getAccountName() . ' (' . $target_user->id() . ')',
    ], 'Create flagging');

    // Verify flagging was created.
    $is_flagged = $this->flag->isFlagged($this->node, $target_user);
    $this->assertTrue($is_flagged, 'The node should be flagged for the target user.');
  }

  /**
   * Tests creating a new user while flagging.
   */
  public function testFlagWithNewUser() {
    $this->drupalLogin($this->adminUser);

    // Go to FSE form.
    $this->drupalGet('flag_fse/confirm/flag/bookmark/' . $this->node->id() . '/default');

    // Select "New user" option.
    $this->submitForm([
      'user_type' => 'new',
    ], 'Create flagging');

    // Should now see new user fields.
    $this->assertSession()->fieldExists('username');
    $this->assertSession()->fieldExists('mail');

    // Submit with new user data.
    $edit = [
      'user_type' => 'new',
      'username' => 'newuser123',
      'mail' => 'newuser123@example.com',
      'generate' => 1,
    ];
    $this->submitForm($edit, 'Create flagging');

    // Verify user was created.
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => 'newuser123']);
    $this->assertNotEmpty($users, 'New user should be created.');

    $new_user = reset($users);

    // Verify flagging was created for the new user.
    $is_flagged = $this->flag->isFlagged($this->node, $new_user);
    $this->assertTrue($is_flagged, 'The node should be flagged for the newly created user.');
  }

  /**
   * Tests that duplicate flaggings are prevented in the form.
   */
  public function testPreventDuplicateFlaggingInForm() {
    $this->drupalLogin($this->adminUser);

    $target_user = $this->drupalCreateUser(['access content']);

    // Flag once programmatically.
    $flagging_service = \Drupal::service('flag');
    $flagging_service->flag($this->flag, $this->node, $target_user);

    // Try to flag again via form.
    $this->drupalGet('flag_fse/confirm/flag/bookmark/' . $this->node->id() . '/default');

    $this->submitForm([
      'uid' => $target_user->getAccountName() . ' (' . $target_user->id() . ')',
    ], 'Create flagging');

    // Should see error message.
    $this->assertSession()->pageTextContains('The user has already flagged this entity.');
  }

  /**
   * Tests access control to FSE form.
   */
  public function testAccessControl() {
    // Try to access FSE form without permission.
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('flag_fse/confirm/flag/bookmark/' . $this->node->id() . '/default');

    // Should be denied.
    $this->assertSession()->statusCodeEquals(403);

    // Login with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('flag_fse/confirm/flag/bookmark/' . $this->node->id() . '/default');

    // Should have access.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests validation of new user creation.
   */
  public function testNewUserValidation() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('flag_fse/confirm/flag/bookmark/' . $this->node->id() . '/default');

    // Try to create user with existing username.
    $existing_user = $this->drupalCreateUser();

    $edit = [
      'user_type' => 'new',
      'username' => $existing_user->getAccountName(),
      'mail' => 'unique@example.com',
      'generate' => 1,
    ];
    $this->submitForm($edit, 'Create flagging');

    // Should see validation error.
    $this->assertSession()->pageTextContains('The username ' . $existing_user->getAccountName() . ' is already taken.');

    // Try with existing email.
    $edit = [
      'user_type' => 'new',
      'username' => 'uniqueusername',
      'mail' => $existing_user->getEmail(),
      'generate' => 1,
    ];
    $this->submitForm($edit, 'Create flagging');

    // Should see validation error.
    $this->assertSession()->pageTextContains('already taken');
  }

}

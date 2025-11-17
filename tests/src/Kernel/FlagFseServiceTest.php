<?php

namespace Drupal\Tests\flag_fse\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Drupal\flag\Entity\Flag;

/**
 * Tests Flag FSE flagging creation and validation.
 *
 * @group flag_fse
 */
class FlagFseServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'flag',
    'flag_fse',
  ];

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('flagging');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter', 'node', 'flag']);

    $this->flagService = $this->container->get('flag');

    // Create a node type.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    // Create a flag.
    $this->flag = Flag::create([
      'id' => 'test_flag',
      'label' => 'Test Flag',
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $this->flag->save();

    // Create a test node.
    $this->node = Node::create([
      'type' => 'article',
      'title' => 'Test Node',
      'status' => 1,
    ]);
    $this->node->save();
  }

  /**
   * Tests that a user can flag content on behalf of another user.
   */
  public function testFlagForSomeoneElse() {
    // Create actor (the one doing the flagging).
    $actor = User::create([
      'name' => 'actor',
      'mail' => 'actor@example.com',
      'status' => 1,
    ]);
    $actor->save();

    // Create target user (the one being flagged for).
    $target_user = User::create([
      'name' => 'target',
      'mail' => 'target@example.com',
      'status' => 1,
    ]);
    $target_user->save();

    // Flag the node for the target user.
    $this->flagService->flag($this->flag, $this->node, $target_user);

    // Verify the flagging exists.
    $is_flagged = $this->flag->isFlagged($this->node, $target_user);
    $this->assertTrue($is_flagged, 'The node should be flagged for the target user.');

    // Verify it's NOT flagged for the actor.
    $is_flagged_by_actor = $this->flag->isFlagged($this->node, $actor);
    $this->assertFalse($is_flagged_by_actor, 'The node should not be flagged for the actor.');
  }

  /**
   * Tests that duplicate flaggings are prevented.
   */
  public function testPreventDuplicateFlagging() {
    $user = User::create([
      'name' => 'testuser',
      'mail' => 'test@example.com',
      'status' => 1,
    ]);
    $user->save();

    // Flag once.
    $this->flagService->flag($this->flag, $this->node, $user);

    // Verify it's flagged.
    $this->assertTrue($this->flag->isFlagged($this->node, $user));

    // Try to flag again - should not create duplicate.
    $this->flagService->flag($this->flag, $this->node, $user);

    // Get all flaggings for this user and node.
    $flaggings = $this->flagService->getFlaggings($this->flag, $this->node, $user);

    $this->assertCount(1, $flaggings, 'Should only have one flagging, not a duplicate.');
  }

  /**
   * Tests that unflagging works correctly.
   */
  public function testUnflagForSomeoneElse() {
    $user = User::create([
      'name' => 'testuser',
      'mail' => 'test@example.com',
      'status' => 1,
    ]);
    $user->save();

    // Flag the node.
    $this->flagService->flag($this->flag, $this->node, $user);
    $this->assertTrue($this->flag->isFlagged($this->node, $user));

    // Unflag the node.
    $this->flagService->unflag($this->flag, $this->node, $user);
    $this->assertFalse($this->flag->isFlagged($this->node, $user));
  }

}

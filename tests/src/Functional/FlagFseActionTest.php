<?php

/**
 * Tests the Flag FSE action plugin.
 *
 * @group flag_fse
 */
class FlagFseActionTest extends BrowserTestBase {

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
    'views',
    'views_ui',
  ];

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

    // Create flag.
    $flag = Flag::create([
      'id' => 'bookmark',
      'label' => 'Bookmark',
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
    ]);
    $flag->save();
  }

  /**
   * Tests that the FSE action appears in available actions.
   */
  public function testActionAvailability() {
    $admin_user = $this->drupalCreateUser([
      'administer actions',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/system/actions');

    // The FSE action should be available.
    $this->assertSession()->pageTextContains('Flag for someone else');
  }

}

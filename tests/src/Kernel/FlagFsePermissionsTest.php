<?php

namespace Drupal\Tests\flag_fse\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\flag_fse\FlagFsePermissions;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\FlagInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Tests for FlagFsePermissions.
 *
 * @group flag_fse
 * @coversDefaultClass \Drupal\flag_fse\FlagFsePermissions
 */
class FlagFsePermissionsTest extends UnitTestCase {

  /**
   * The flag service mock.
   *
   * @var \Drupal\flag\FlagServiceInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $flagService;

  /**
   * The permissions service.
   *
   * @var \Drupal\flag_fse\FlagFsePermissions
   */
  protected $permissionsService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->flagService = $this->createMock(FlagServiceInterface::class);

    // Create the permissions service.
    $this->permissionsService = new FlagFsePermissions($this->flagService);

    // Mock the string translation service.
    $string_translation = $this->getStringTranslationStub();
    $this->permissionsService->setStringTranslation($string_translation);
  }

  /**
   * Tests that permissions are generated correctly for each flag.
   *
   * @covers ::permissions
   */
  public function testPermissionsGeneration() {
    // Create mock flags.
    $flag1 = $this->createMock(FlagInterface::class);
    $flag1->method('id')->willReturn('bookmark');
    $flag1->method('label')->willReturn('Bookmark');

    $flag2 = $this->createMock(FlagInterface::class);
    $flag2->method('id')->willReturn('like');
    $flag2->method('label')->willReturn('Like');

    $flags = [
      'bookmark' => $flag1,
      'like' => $flag2,
    ];

    $this->flagService
      ->method('getAllFlags')
      ->willReturn($flags);

    $permissions = $this->permissionsService->permissions();

    // Assert that we have 2 permissions.
    $this->assertCount(2, $permissions);

    // Assert bookmark permission exists.
    $this->assertArrayHasKey('flag fse bookmark', $permissions);
    $this->assertIsArray($permissions['flag fse bookmark']);
    $this->assertArrayHasKey('title', $permissions['flag fse bookmark']);

    // The title should be a TranslatableMarkup or string.
    $title = (string) $permissions['flag fse bookmark']['title'];
    $this->assertNotEmpty($title);

    // Assert like permission exists.
    $this->assertArrayHasKey('flag fse like', $permissions);
    $this->assertIsArray($permissions['flag fse like']);
    $this->assertArrayHasKey('title', $permissions['flag fse like']);

    $title = (string) $permissions['flag fse like']['title'];
    $this->assertNotEmpty($title);
  }

  /**
   * Tests that no permissions are generated when no flags exist.
   *
   * @covers ::permissions
   */
  public function testNoPermissionsWhenNoFlags() {
    $this->flagService
      ->method('getAllFlags')
      ->willReturn([]);

    $permissions = $this->permissionsService->permissions();

    $this->assertEmpty($permissions);
  }

  /**
   * Tests permission format.
   *
   * @covers ::permissions
   */
  public function testPermissionFormat() {
    $flag = $this->createMock(FlagInterface::class);
    $flag->method('id')->willReturn('test_flag');
    $flag->method('label')->willReturn('Test Flag');

    $this->flagService
      ->method('getAllFlags')
      ->willReturn(['test_flag' => $flag]);

    $permissions = $this->permissionsService->permissions();

    // Check the permission key format.
    $this->assertArrayHasKey('flag fse test_flag', $permissions);

    // Check that the permission has required properties.
    $permission = $permissions['flag fse test_flag'];
    $this->assertIsArray($permission);
    $this->assertArrayHasKey('title', $permission);

    // Title should exist and not be empty.
    $this->assertNotEmpty($permission['title']);
  }

}

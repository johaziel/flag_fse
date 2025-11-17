<?php

namespace Drupal\Tests\flag_fse\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\flag_fse\FlagFsePermissions;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\FlagInterface;

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
    $this->permissionsService = new FlagFsePermissions($this->flagService);
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

    // Assert bookmark permission.
    $this->assertArrayHasKey('flag fse bookmark', $permissions);
    $this->assertStringContainsString('Bookmark', (string) $permissions['flag fse bookmark']['title']);

    // Assert like permission.
    $this->assertArrayHasKey('flag fse like', $permissions);
    $this->assertStringContainsString('Like', (string) $permissions['flag fse like']['title']);
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

}

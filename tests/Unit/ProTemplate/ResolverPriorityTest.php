<?php
/**
 * Unit tests for Pro Template Resolver priority + applicability logic.
 *
 * Resolver pulls candidates from #__flexicontent_pro_layouts then picks
 * the one with the lowest priority score (item < menu < category < type
 * < global). These tests cover the priority + applicability logic via
 * a tiny stand-in for FlexicontentProTemplateResolver::isApplicable.
 *
 * Logic mirror — keep this file in sync with the production helper at
 * admin/helpers/protemplate/Resolver.php.
 *
 * @package  FLEXIcontent
 * @since    6.1.0
 */

namespace FLEXIcontent\Tests\Unit\ProTemplate;

use PHPUnit\Framework\TestCase;

class ResolverPriorityTest extends TestCase
{
	/** Priority weights — must match the production helper. */
	private const PRIORITY = [
		'item'     => 1,
		'menu'     => 2,
		'category' => 3,
		'type'     => 4,
		'global'   => 5,
	];

	/**
	 * Strict applicability check (mirror of the protected method).
	 */
	private function isApplicable(array $row, array $context): bool
	{
		switch ($row['assignment_type']) {
			case 'item':
				return (string) $row['assignment_value'] === (string) $context['item_id']
					&& (int) $context['item_id'] > 0;
			case 'menu':
				return (string) $row['assignment_value'] === (string) $context['menu_id']
					&& (int) $context['menu_id'] > 0;
			case 'category':
				return (int) $row['catid'] === (int) $context['cat_id']
					&& (int) $context['cat_id'] > 0;
			case 'type':
				return (int) $row['type_id'] === (int) $context['type_id']
					&& (int) $context['type_id'] > 0;
			case 'global':
				return true;
		}
		return false;
	}

	private function resolve(array $candidates, array $context): ?array
	{
		$best     = null;
		$bestRank = PHP_INT_MAX;
		foreach ($candidates as $row) {
			if (!$this->isApplicable($row, $context)) continue;
			$rank = self::PRIORITY[$row['assignment_type']] ?? PHP_INT_MAX;
			if ($rank < $bestRank) {
				$best     = $row;
				$bestRank = $rank;
			}
		}
		return $best;
	}

	// ─────────────────────────────────────────────────────────────────

	/** @test */
	public function item_assignment_beats_all_others(): void
	{
		$ctx = ['item_id' => 17, 'cat_id' => 5, 'type_id' => 2, 'menu_id' => 33];
		$rows = [
			['id' => 1, 'assignment_type' => 'global', 'assignment_value' => '', 'catid' => 0, 'type_id' => 0],
			['id' => 2, 'assignment_type' => 'type',   'assignment_value' => '', 'catid' => 0, 'type_id' => 2],
			['id' => 3, 'assignment_type' => 'category','assignment_value' => '', 'catid' => 5, 'type_id' => 0],
			['id' => 4, 'assignment_type' => 'menu',   'assignment_value' => '33','catid' => 0, 'type_id' => 0],
			['id' => 5, 'assignment_type' => 'item',   'assignment_value' => '17','catid' => 0, 'type_id' => 0],
		];

		$best = $this->resolve($rows, $ctx);
		$this->assertNotNull($best);
		$this->assertSame(5, $best['id']);
	}

	/** @test */
	public function menu_assignment_beats_category_type_global(): void
	{
		$ctx = ['item_id' => 17, 'cat_id' => 5, 'type_id' => 2, 'menu_id' => 33];
		$rows = [
			['id' => 1, 'assignment_type' => 'global', 'assignment_value' => '', 'catid' => 0, 'type_id' => 0],
			['id' => 2, 'assignment_type' => 'type',   'assignment_value' => '', 'catid' => 0, 'type_id' => 2],
			['id' => 3, 'assignment_type' => 'category','assignment_value' => '', 'catid' => 5, 'type_id' => 0],
			['id' => 4, 'assignment_type' => 'menu',   'assignment_value' => '33','catid' => 0, 'type_id' => 0],
		];

		$best = $this->resolve($rows, $ctx);
		$this->assertSame(4, $best['id']);
	}

	/** @test */
	public function category_assignment_beats_type_global(): void
	{
		$ctx = ['item_id' => 0, 'cat_id' => 5, 'type_id' => 2, 'menu_id' => 0];
		$rows = [
			['id' => 1, 'assignment_type' => 'global', 'assignment_value' => '', 'catid' => 0, 'type_id' => 0],
			['id' => 2, 'assignment_type' => 'type',   'assignment_value' => '', 'catid' => 0, 'type_id' => 2],
			['id' => 3, 'assignment_type' => 'category','assignment_value' => '', 'catid' => 5, 'type_id' => 0],
		];

		$best = $this->resolve($rows, $ctx);
		$this->assertSame(3, $best['id']);
	}

	/** @test */
	public function type_assignment_beats_global(): void
	{
		$ctx = ['item_id' => 0, 'cat_id' => 0, 'type_id' => 2, 'menu_id' => 0];
		$rows = [
			['id' => 1, 'assignment_type' => 'global', 'assignment_value' => '', 'catid' => 0, 'type_id' => 0],
			['id' => 2, 'assignment_type' => 'type',   'assignment_value' => '', 'catid' => 0, 'type_id' => 2],
		];

		$best = $this->resolve($rows, $ctx);
		$this->assertSame(2, $best['id']);
	}

	/** @test */
	public function global_fallback_when_no_specific_match(): void
	{
		$ctx = ['item_id' => 0, 'cat_id' => 0, 'type_id' => 0, 'menu_id' => 0];
		$rows = [
			['id' => 1, 'assignment_type' => 'global', 'assignment_value' => '', 'catid' => 0, 'type_id' => 0],
			['id' => 2, 'assignment_type' => 'type',   'assignment_value' => '', 'catid' => 0, 'type_id' => 99], // mismatches
		];

		$best = $this->resolve($rows, $ctx);
		$this->assertSame(1, $best['id']);
	}

	/** @test */
	public function null_when_no_match_at_all(): void
	{
		$ctx = ['item_id' => 1, 'cat_id' => 1, 'type_id' => 1, 'menu_id' => 1];
		$rows = [
			['id' => 1, 'assignment_type' => 'type',     'assignment_value' => '',  'catid' => 0, 'type_id' => 99],
			['id' => 2, 'assignment_type' => 'category', 'assignment_value' => '',  'catid' => 99,'type_id' => 0],
			['id' => 3, 'assignment_type' => 'item',     'assignment_value' => '99','catid' => 0, 'type_id' => 0],
		];

		$best = $this->resolve($rows, $ctx);
		$this->assertNull($best);
	}

	/** @test */
	public function item_assignment_value_string_match_strict(): void
	{
		// Defensive: assignment_value is VARCHAR(100) so string compare.
		$ctx = ['item_id' => 17, 'cat_id' => 0, 'type_id' => 0, 'menu_id' => 0];
		$rows = [
			['id' => 1, 'assignment_type' => 'item', 'assignment_value' => '17',   'catid' => 0, 'type_id' => 0],
			['id' => 2, 'assignment_type' => 'item', 'assignment_value' => '170',  'catid' => 0, 'type_id' => 0],
			['id' => 3, 'assignment_type' => 'item', 'assignment_value' => '1700', 'catid' => 0, 'type_id' => 0],
		];

		$best = $this->resolve($rows, $ctx);
		$this->assertSame(1, $best['id']);
	}

	/** @test */
	public function applicability_rejects_zero_context_ids(): void
	{
		// Item ID 0 should never match item-assignment rows even with
		// assignment_value '0' (defensive — production code should never
		// store assignment_value='0' on item-type rows).
		$ctx = ['item_id' => 0, 'cat_id' => 0, 'type_id' => 0, 'menu_id' => 0];
		$rows = [
			['id' => 1, 'assignment_type' => 'item', 'assignment_value' => '0', 'catid' => 0, 'type_id' => 0],
		];
		$best = $this->resolve($rows, $ctx);
		$this->assertNull($best);
	}

	/** @test */
	public function clamp_heading_logic_item_context(): void
	{
		// Mirror of FlexicontentProTemplateRenderer::clampHeading
		// in 'item' context.
		$depth      = 0;        // 'item' context starts at 0 (no heading emitted yet)
		$h1Consumed = false;

		$clamp = function (string $requested) use (&$depth, &$h1Consumed): string {
			$reqLevel = (int) substr($requested, 1);
			$reqLevel = max(1, min(6, $reqLevel));
			if (!$h1Consumed && $reqLevel === 1) {
				$h1Consumed = true;
				$depth      = 1;
				return 'h1';
			}
			$floor = max(2, $depth);
			$out   = max($reqLevel, $floor);
			$out   = max(1, min(6, $out));
			$depth = $out;
			return 'h' . $out;
		};

		// First request: h1 granted (item title)
		$this->assertSame('h1', $clamp('h1'));
		// Second h1 attempt: clamped to h2 (no second h1 allowed)
		$this->assertSame('h2', $clamp('h1'));
		// User wants h5: stays h5 (going deeper is allowed)
		$this->assertSame('h5', $clamp('h5'));
		// User wants h2: clamped UP to h5 floor (no level skipping back)
		$this->assertSame('h5', $clamp('h2'));
		// h6 stays
		$this->assertSame('h6', $clamp('h6'));
	}

	/** @test */
	public function clamp_heading_logic_category_context(): void
	{
		// In 'category' context, h1 belongs to the category view.
		// All Pro Template headings must be h2 or deeper.
		$depth      = 2;
		$h1Consumed = true;  // pre-consumed outside 'item' context

		$clamp = function (string $requested) use (&$depth, &$h1Consumed): string {
			$reqLevel = (int) substr($requested, 1);
			$reqLevel = max(1, min(6, $reqLevel));
			if (!$h1Consumed && $reqLevel === 1) {
				$h1Consumed = true;
				$depth      = 1;
				return 'h1';
			}
			$floor = max(2, $depth);
			$out   = max($reqLevel, $floor);
			$out   = max(1, min(6, $out));
			$depth = $out;
			return 'h' . $out;
		};

		// All h1 requests demoted to h2 in non-item context
		$this->assertSame('h2', $clamp('h1'));
		$this->assertSame('h3', $clamp('h3'));
		$this->assertSame('h6', $clamp('h6'));
	}
}

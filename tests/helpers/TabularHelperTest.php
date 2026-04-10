<?php

namespace Tests\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TabularHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        helper('tabular');
    }

    public function testFormatSupplierLunaSummaryFormatsLandownerLunasAsBullets(): void
    {
        $summary = format_supplier_luna_summary([
            [
                'area_name'   => 'North Field',
                'tenant_name' => 'Mario Dela Cruz',
            ],
            [
                'area_name'   => 'South Field',
                'tenant_name' => 'Ana Reyes',
            ],
        ], LAND_OWNER_SUPPLIER);

        $this->assertSame(
            '<ul class="supplier-luna-list" style="margin:0; padding-left:18px;"><li>North Field (Mario Dela Cruz)</li><li>South Field (Ana Reyes)</li></ul>',
            $summary,
        );
    }

    public function testFormatSupplierLunaSummaryKeepsTenantLandownerContext(): void
    {
        $summary = format_supplier_luna_summary([
            [
                'area_name'      => 'North Field',
                'landowner_name' => 'Lelia Joson',
            ],
        ], TENANT_SUPPLIER);

        $this->assertSame(
            '<ul class="supplier-luna-list" style="margin:0; padding-left:18px;"><li>North Field [Lelia Joson]</li></ul>',
            $summary,
        );
    }
}
